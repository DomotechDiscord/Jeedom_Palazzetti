<?php
/* 
 */
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Palazzetti extends eqLogic
{
	public static function cron()
	{
		$autorefresh = config::byKey('autorefresh', 'Palazzetti');
		$numberOfTryBeforeEqLogicDisable = 3;
		if ($autorefresh != '') {
			try {
				$cron = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
				if ($cron->isDue()) {
					log::add(__CLASS__, 'debug', __("Démarrage du cron ", __FILE__). $autorefresh);
					foreach (eqLogic::byType('Palazzetti') as $Palazzetti) {
						/** seulement si activé **/
						if($Palazzetti->getIsEnable()){
							try {
								$Palazzetti->getInformations();
								$mc = cache::byKey('PalazzettiWidgetmobile' . $Palazzetti->getId());
								$mc->remove();
								$mc = cache::byKey('PalazzettiWidgetdashboard' . $Palazzetti->getId());
								$mc->remove();
								$Palazzetti->toHtml('mobile');
								$Palazzetti->toHtml('dashboard');
								$Palazzetti->refreshWidget();

								// mise à jour horloge 
								$date = date("Y-m-d H:i:s");
								//$DATA = $Palazzetti->makeRequest($cmdString);
							} catch (Exception $exc) {
								if (config::byKey('autoDisable', 'Palazzetti', '', true)) {
									/** Sans réponse 3 fois, je désactive l'équipement **/
									$numberTryWithoutSuccess = $Palazzetti->getStatus('numberTryWithoutSuccess', 0);
									$numberTryWithoutSuccess++;
									$Palazzetti->setStatus('numberTryWithoutSuccess', $numberTryWithoutSuccess);
									if ($numberTryWithoutSuccess >= $numberOfTryBeforeEqLogicDisable) {
										$Palazzetti->setIsEnable(0);
										$Palazzetti->save();
									}
								}
							}
						}
					}
					log::add(__CLASS__, 'debug', __("Fin d'exécution du cron ", __FILE__). $autorefresh);
				}
			} catch (Exception $exc) {
				log::add(__CLASS__, 'error', __("Erreur lors de l'exécution du cron ", __FILE__) . $exc->getMessage());
			}
		}
		log::add(__CLASS__, 'debug', __FUNCTION__ . __(' : fin', __FILE__));
	}

	// apres creation équipement
	public function postInsert()
	{
		/** forcer à 15 min à la première mise en service **/
		if(config::byKey('autorefresh', 'Palazzetti') == '') {
			config::save("*/15 * * * *", 'autorefresh', 'Palazzetti');
        }
    }

	// apres sauvegarde équipement
	public function preSave()
	{
		/** selection du fichier de config pour créer les commandes **/
	    $PalaControl = $this->getConfiguration('PalaControl');
		if ($PalaControl == 0) {
			$configFile = "Palazzetti";
		} else {
			$configFile = "PalaControl";
		}
		$TabCmd = $this->loadCmdFromConf($configFile);

		//Chaque commande
		$Order = 0;
		if (is_array($TabCmd) || is_object($TabCmd)) {

			foreach ($TabCmd as $CmdKey => $Cmd) {

				$PalazzettiCmd = $this->getCmd(null, $Cmd['LogicalId']);

				if (!is_object($PalazzettiCmd)) {
					$PalazzettiCmd = new PalazzettiCmd();
				}
				$PalazzettiCmd->setName($Cmd['Libelle']);
				$PalazzettiCmd->setEqLogic_id($this->getId());
				$PalazzettiCmd->setLogicalId($Cmd['LogicalId']);
				$PalazzettiCmd->setType($Cmd['Type']);
				$PalazzettiCmd->setSubType($Cmd['SubType']);
				$PalazzettiCmd->setIsVisible($Cmd['visible']);
				if ($Cmd['Type'] == "action") {
					$PalazzettiCmd->setConfiguration('actionCmd', $Cmd['actionCmd']);
					$PalazzettiCmd->setConfiguration('updateLogicalId', $Cmd['updateLogicalId']);
				}
				if ($Cmd['SubType'] == "slider") {
					$PalazzettiCmd->setConfiguration('nparams', $Cmd['nparams']);
					$PalazzettiCmd->setConfiguration('parameters', $Cmd['parameters']);
					$PalazzettiCmd->setConfiguration('minValue', $Cmd['minValue']);
					$PalazzettiCmd->setConfiguration('maxValue', $Cmd['maxValue']);
				}
				if ($Cmd['Unite'] != '') {
					$PalazzettiCmd->setUnite($Cmd['Unite']);
				}
				if ($Cmd['IsHistorized'] == true) {
					$PalazzettiCmd->setIsHistorized(1);
				}
				$PalazzettiCmd->setOrder($Order);
				$PalazzettiCmd->save();
				$Order++;
			}
		}
	}

	public function preUpdate()
	{
		/** refuser si l'adresse est vide lors de l'enregistrement **/
		if (empty($this->getConfiguration('addressip'))) {
			throw new Exception(__('L\'adresse IP ne peut pas être vide',__FILE__));
		}
	}

	public function postUpdate()
	{
		/** si équipement actif, rafraichir les infos de cet équipement **/
		if($this->getIsEnable()){
			$this->getInformations();
		}
	}

	public static $_widgetPossibility = array('custom' => array(
		'visibility' => true,
		'displayName' => true,
		'displayObjectName' => true,
		'optionalParameters' => true,
		'background-color' => true,
		'text-color' => true,
		'border' => true,
		'border-radius' => true,
		'background-opacity' => true,
	));

	/** méthode de récupération des fichiers de configuration **/
	public function loadCmdFromConf($type) {

		$return = array();
		if (!is_file(dirname(__FILE__) . '/../../core/config/' . $type . '.json')) {
			log::add(__CLASS__, 'debug', 'Fichier introuvable : ' . dirname(__FILE__) . '/config/' . $type . '.json');
			return false;
		}
		$content = file_get_contents(dirname(__FILE__) . '/../../core/config/' . $type . '.json');
		if (!is_json($content)) {
			log::add(__CLASS__, 'debug', 'JSON invalide : ' . $type . '.json');
			return false;
		}
		$device = json_decode($content, true);
		if (!is_array($device) || !isset($device)) {
			log::add(__CLASS__, 'debug', 'Tableau incorrect : ' . $type . '.json');
			return false;
		}

		return $device;
	}

	/** méthode d'envoi des requêtes **/
	public function makeRequest($cmd)
	{
		if ($cmd == 'gsw' || $cmd == 'ffffffff') {
			$url = 'http://' . $this->getConfiguration('addressip') . '/' . $cmd;
		} else {
			$url = 'http://' . $this->getConfiguration('addressip') . '/cgi-bin/sendmsg.lua?cmd=' . $cmd;
		}
		log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'get URL ' . $url);

		$request_http = new com_http($url);
		$return = $request_http->exec(5,3);
		$return = json_decode($return);

		if ($return->INFO->RSP == 'OK') {
			return false;
		} else {
			return $return;
		}
	}

	// interpretation valeur ventilateur
	public function getFanState($num)
	{
	    if ($this->getConfiguration('ModeHIGH') == 0) {
            switch ($num) {
                case 0:
                case 6:
                    $value = 'AUTO';
                    break;
                case 7:
                    $value = 'OFF';
                    break;
                default:
                    $value = $num;
            }
        }else {
            switch ($num) {
                case 0:
                case 6:
                    $value = 'HIGH';
                    break;
                case 7:
                    $value = 'AUTO';
                    break;
                default:
                    $value = $num;
            }
        }
		return $value;
	}

	public static function getFanStateF3L($num)
	{
		switch ($num) {
			case 0:
				$value = 'OFF';
				break;
			case 1:
				$value = 'ON';
				break;
		}
		return $value;
	}

	public static function getFanStateF4L($num)
	{
		switch ($num) {
			case 0:
				$value = 'OFF';
				break;
			case 1:
				$value = 'ON';
				break;
		}
		return $value;
	}

	// interpretation valeur status poele
    public static function getStoveState($num)
    {
        $lib[0] = 'Eteint';
        $lib[1] = 'Arrêté';
        $lib[2] = 'Vérification';
        $lib[3] = 'Chargement granulés';
        $lib[4] = 'Allumage';
        $lib[5] = 'Contrôle combustion';
        $lib[6] = 'En chauffe';
        $lib[9] = 'Diffusion';
        $lib[10] = 'Extinction';
        $lib[11] = 'Nettoyage';
        $lib[12] = 'Refroidissement';
        $lib[241] = 'Erreur Nettoyage';
        $lib[243] = 'Erreur Grille';
        $lib[244] = 'NTC2 ALARM';
        $lib[245] = 'NTC3 ALARM';
        $lib[247] = 'Erreur Porte';
        $lib[248] = 'Erreur Dépression';
        $lib[249] = 'NTC1 ALARM';
        $lib[250] = 'TC1 ALARM';
        $lib[252] = 'Erreur évacuation Fumée';
        $lib[253] = 'Pas de pellets';
        if (isset($lib[$num])) {
            return $lib[$num];
        } else {
            return $num;
        }
	}

	// methode jour de la semaine
	public static function getWeekDay($num)
	{
		$lib[1] = 'Lundi';
		$lib[2] = 'Mardi';
		$lib[3] = 'Mercredi';
		$lib[4] = 'Jeudi';
		$lib[5] = 'Vendredi';
		$lib[6] = 'Samedi';
		$lib[7] = 'Dimanche';
		if (isset($lib[$num])) {
			return $lib[$num];
		} else {
			return 'Jour #' . $num;
		}
	}
	// methode traitement commande
	public function sendCommand($CMD, $_options)
	{
		// requete http
		$cmdString = $CMD->getConfiguration('actionCmd');
		// si option value ajout dans la requete
		if (isset($_options) && $_options != '') {
			if (is_array($_options)) {
				// cas ph
				if (isset($_options['jour']) && isset($_options['tranche']) && isset($_options['programme'])) {
					$cmdString = $cmdString . $_options['jour'] . '+' . $_options['tranche'] . '+' . $_options['programme'];
				} else if (isset($_options['numero']) && isset($_options['temperature']) && isset($_options['h1']) && isset($_options['m1']) && isset($_options['h2']) && isset($_options['m2'])) {
					$cmdString = $cmdString . $_options['numero'] . '+' . $_options['temperature'] . '+' . $_options['h1'] . '+' . $_options['m1'] . '+' . $_options['h2'] . '+' . $_options['m2'];
				} else if (isset($_options['slider'])) {
					$cmdString = $cmdString . $_options['slider'];
				}
			} else {
				$cmdString = $cmdString . $_options;
			}
			log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' commande ' . $cmdString);
			log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' commande ' . json_encode($_options));
		}
		$DATA = $this->makeRequest($cmdString);

		if ($DATA == false) {
			return 'ERROR';
		}
		// verification succes du traitement
	    if ($this->getConfiguration('PalaControl') == 0) {
			if ($DATA->INFO->RSP != 'OK') {
				log::add('Palazzetti', 'error', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' erreur ' . $CMD . ' : ' . $DATA->INFO->RSP);
				return false;
			}
		} else {
			if ($DATA->SUCCESS != 'true' && !is_object($DATA)) {
				log::add('Palazzetti', 'error', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' erreur ' . $CMD . ' : ' . json_encode($DATA));
				return false;
			}
		}

		// definition patern de comparaison
		$expl = explode('+', $cmdString);
		$pattern = $expl[0] . '+' . $expl[1];

		if ($this->getConfiguration('PalaControl') == 0) {
			// traitement suivant commande
			switch ($pattern) {
				// allumage, extinction, status
				case 'CMD+ON':
				case 'CMD+OFF':
				case 'GET+STAT':
					$value = $this->getStoveState($DATA->Status->STATUS);
                break;
				// nom poele
				case 'GET+LABL':
				case 'SET+LABL':
					$value = $DATA->StoveData->LABEL;
                break;
				// force du feu
				case 'SET+POWR':
					$value = $DATA->DATA->PWR;
                break;
				// température de consigne
				case 'GET+SETP':
				case 'SET+SETP':
					$value = $DATA->DATA->SETP;
                break;
				// force du ventilateur
				case 'GET+FAND':
					$value = $this->getFanState($DATA->Fans->FAN_FAN2LEVEL);
                break;
				case 'SET+RFAN':
					$value = $this->getFanState($DATA->DATA->F2L);
                break;
				// force ventilateur F3L
				case 'SET+FN3L':
					$value = $this->getFanState($DATA->DATA->F3L);
					break;
				// force ventilateur F4L
				case 'SET+FN4L':
					$value = $this->getFanState($DATA->DATA->F4L);
					break;
				// température ambiance
				case 'GET+TMPS':
					$value = $DATA->DATA->T1;
					break;
				// programmes horaires
				case 'GET+CHRD':
					$value = json_encode($DATA->DATA);
					break;
				// programmes horaires
				case 'SET+CSST':
					break;
				// affectation programme
				// options +JOUR+TRANCHE+PH
				case 'SET+CDAY':
					break;
				// informations automate
				case 'EXT+ADRD':
					$value = $DATA->DATA->{'ADDR_' . $expl[2]};
					log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'reponse ' . $value);
					break;
			}
        } else {
			// traitement suivant commande
			switch ($pattern) {
					// status
				case 'GET+STAT':
					$value = $this->getStoveState($DATA->DATA->STATUS);
					break;
					// heure du poele
				case 'GET+TIME':
					$value = $DATA->DATA->STOVE_DATETIME;
					break;
					// force du feu
				case 'GET+POWR':
					$value = $DATA->DATA->PWR;
					//FDR ?
					break;
					// température de consigne
				case 'GET+SETP':
					$value = $DATA->DATA->SETP;
					break;
					// force des ventilateurs via tableau
				case 'GET+FAND':
					$value['RFan'] = $this->getFanState($DATA->DATA->F2L);
					$value['IFanF3L'] = $this->getFanState($DATA->DATA->F3L);
					$value['IFanF4L'] = $this->getFanState($DATA->DATA->F4L);
					break;
					// températures ambiance, granulés et fumées-combustion via tableau
				case 'GET+TMPS':
					$value['ITemp'] = $DATA->DATA->T1;
					$value['ITemp2'] = $DATA->DATA->T2;
					$value['ITemp3'] = $DATA->DATA->T3;
					break;
			}
		}

		// mise a jour variables info
		if ($CMD->getConfiguration('updateLogicalId')) {
			/** si tableau, mise à jour des ventilateurs ou température **/
			if (is_array($value)) {
				foreach ($value as $logicId => $val){
					$INFO = $this->getCmd(null, $logicId);
					$INFO->event($val);
					$INFO->save();
					log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'response ' . $val);
					log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'updatelogicalId ' .  $logicId . ' = ' . $val);
				}
			} else {
				$INFO = $this->getCmd(null, $CMD->getConfiguration('updateLogicalId'));
				$INFO->event($value);
				$INFO->save();
				log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'response ' . $value);
				log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'updatelogicalId ' .  $CMD->getConfiguration('updateLogicalId') . ' = ' . $value);
			}
		}
		// mise à jour lastvalue commande
		$CMD->setConfiguration('lastCmdValue', $value);
		$CMD->save();
		return 'OK';
	}

	public function toHtml($_version = 'dashboard')
	{
		/** pour ne pas utiliser le template widget de l'équipement **/
		if ($this->getConfiguration('widgetTemplate') != 1) {
    		return parent::toHtml($_version);
    	}
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}

		$temps = $this->getCmd(null, 'ITemp');
		$replace['#temperature#'] = $temps->execCmd();

		$status = $this->getCmd(null, 'IStatus');
		$replace['#status#'] = $this->getStoveState($status->execCmd());
		$WOn = $this->getCmd(null, 'WOn');
		$replace['#on_id#'] = is_object($WOn) ? $WOn->getId() : '';
		$WOff = $this->getCmd(null, 'WOff');
		$replace['#off_id#'] = is_object($WOff) ? $WOff->getId() : '';

		$consigne = $this->getCmd(null, 'IConsigne');
		$replace['#consigne#'] = $consigne->execCmd();
		$Wconsigne = $this->getCmd(null, 'WConsigne');
		$replace['#consigne_id#'] = is_object($Wconsigne) ? $Wconsigne->getId() : '';

		$fan = $this->getCmd(null, 'IFan');
		$replace['#fan#'] = $this->getFanState($fan->execCmd());
		$Wfan = $this->getCmd(null, 'WFan');
		$replace['#fan_id#'] = is_object($Wfan) ? $Wfan->getId() : '';

		$fanF3L = $this->getCmd(null, 'IFanF3L');
		$replace['#fanF3L#'] = $this->getFanStateF3L($fanF3L->execCmd());
		$WfanF3L = $this->getCmd(null, 'WFanF3L');
		$replace['#fanF3L_id#'] = is_object($WfanF3L) ? $WfanF3L->getId() : '';

		$fanF4L = $this->getCmd(null, 'IFanF4L');
		$replace['#fanF4L#'] = $this->getFanStateF4L($fanF4L->execCmd());
		$WfanF4L = $this->getCmd(null, 'WFanF4L');
		$replace['#fanF4L_id#'] = is_object($WfanF4L) ? $WfanF4L->getId() : '';

		$power = $this->getCmd(null, 'IPower');
		$replace['#power#'] = $power->execCmd();
		$Wpower = $this->getCmd(null, 'Wpower');
		$replace['#power_id#'] = is_object($Wpower) ? $Wpower->getId() : '';

		$refresh = $this->getCmd(null, 'ISnap');
		$replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';

		$html = template_replace($replace, getTemplate('core', $_version, 'Palazzetti', 'Palazzetti'));
		cache::set('PalazzettiWidget' . $_version . $this->getId(), $html, 0);
		return $html;
	}

	// récupération automatique des informations
	public function getInformations()
	{
		// PALAZZETTI
		if ($this->getConfiguration('PalaControl') == 0) {
			// récupération de l'heure
			$DATA = $this->makeRequest('GET+TIME');
			if ($DATA != false) {
				// mise à jour nom du poêle
				$TIME = $this->getCmd(null, 'ITime');
				$TIME->event(json_encode($DATA));
				$TIME->save();
			}
			// récupération infos nom + réseau
			$DATA = $this->makeRequest('GET+STDT');
			if ($DATA != false) {
				// mise à jour nom du poêle
				$LABL = $this->getCmd(null, 'IName');
				$LABL->event($DATA->STOVEDATA->LABEL);
				$LABL->save();
				// mise à jour force du feu
				$POWR = $this->getCmd(null, 'INetwork');
				$POWR->event(json_encode($DATA));
				$POWR->save();
			}
			// récupération de toutes les informations Palazzetti
			$DATA = $this->makeRequest('GET+ALLS');
			if ($DATA != false) {
				// mise à jour force du feu
				$POWR = $this->getCmd(null, 'IPower');
				$POWR->event($DATA->DATA->PWR);
				$POWR->save();
				// mise à jour température de consigne
				$TCON = $this->getCmd(null, 'IConsigne');
				$TCON->event($DATA->DATA->SETP);
				$TCON->save();
				// mise à jour force du ventilateur
				$FAN = $this->getCmd(null, 'IFan');
				$FAN->event($DATA->DATA->F2L);
				$FAN->save();
				// mise à jour force du ventilateur 3 F3L
				$FANF3L = $this->getCmd(null, 'IFanF3L');
				$FANF3L->event($DATA->DATA->F3L);
				$FANF3L->save();
				// mise à jour force du ventilateur 4 F4L
				$FANF4L = $this->getCmd(null, 'IFanF4L');
				$FANF4L->event($DATA->DATA->F4L);
				$FANF4L->save();
				// mise à jour temperature ambiance
				$TMP = $this->getCmd(null, 'ITemp');
				$TMP->event($DATA->DATA->T1);
				$TMP->save();
				// mise à jour status poele
				$STA = $this->getCmd(null, 'IStatus');
				$STA->event($DATA->DATA->STATUS);
				$STA->save();
				// mise a jour variables snap
				$SNAP = $this->getCmd(null, 'ISnap');
				$SNAP->event(json_encode($DATA));
				$SNAP->save();
			}

			// récupération des programmes horaires
			$DATA = $this->makeRequest('GET+CHRD');
			if ($DATA != false) {
				// mise à jour programmes horaires
				$PH = $this->getCmd(null, 'IPH');
				$PH->event(json_encode($DATA->DATA));
				$PH->save();
			}

			// récupération des infos automate
			$DATA = $this->makeRequest('EXT+ADRD+2066+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'INbAllumage');
				$EXT->event($DATA->DATA->ADDR_2066);
				$EXT->save();
			}

			$DATA = $this->makeRequest('EXT+ADRD+207C+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'INbAllumageFailed');
				$EXT->event($DATA->DATA->ADDR_207C);
				$EXT->save();
			}

			$DATA = $this->makeRequest('EXT+ADRD+206A+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'IHeuresAlimElec');
				$EXT->event($DATA->DATA->ADDR_206A);
				$EXT->save();
			}

			$DATA = $this->makeRequest('EXT+ADRD+2070+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'IHeuresChauffe');
				$EXT->event($DATA->DATA->ADDR_2070);
				$EXT->save();
			}

			$DATA = $this->makeRequest('EXT+ADRD+207A+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'IHeuresSurChauffe');
				$EXT->event($DATA->DATA->ADDR_207A);
				$EXT->save();
			}

			$DATA = $this->makeRequest('EXT+ADRD+2076+1');
			if ($DATA != false) {
				$EXT = $this->getCmd(null, 'IHeuresDepuisEntretien');
				$EXT->event($DATA->DATA->ADDR_2076);
				$EXT->save();
			}
		// PALACONTROL
		} else {
			// récupération de l'heure
			$DATA = $this->makeRequest('GET+TIME');
			if ($DATA != false) {
				// mise à jour heure du poele
				$TIME = $this->getCmd(null, 'ITime');
				$TIME->event(json_encode($DATA->DATA->STOVE_DATETIME));
				$TIME->save();
			}
			// récupération des informations réseau
			$DATA = $this->makeRequest('ffffffff');
			if ($DATA != false) {
				// mise à jour nom du poêle
				$LABL = $this->getCmd(null, 'IName');
				$LABL->event($DATA->m);
				$LABL->save();
			}
			$DATA = $this->makeRequest('gsw');
			if ($DATA != false) {
				// mise à jour info connexion
				$POWR = $this->getCmd(null, 'INetwork');
				$POWR->event(json_encode($DATA));
				$POWR->save();
            }
			//récupération des compteurs
			$DATA = $this->makeRequest('GET+CUNT');
			if ($DATA != false) {
				// mise a jour nombre d'allumages
				$EXT = $this->getCmd(null, 'INbAllumage');
				$EXT->event($DATA->DATA->IGN);
				$EXT->save();
				// mise a jour temps allumage (sous tension) 
				$ELEC = $this->getCmd(null, 'IHeuresAlimElec');
				$ELEC->event($DATA->DATA->POWERTIME);
				$ELEC->save();
				// mise a jour temps de chauffe( de travail)
				$CHAUF = $this->getCmd(null, 'IHeuresChauffe');
				$CHAUF->event($DATA->DATA->HEATTIME);
				$CHAUF->save();
				// mise a jour erreur surchauffe
				$EXT = $this->getCmd(null, 'IHeuresSurChauffe');
				$EXT->event($DATA->DATA->OVERTMPERRORS);
				$EXT->save();
				// mise a jour nombre allumage ratés
				$EXT = $this->getCmd(null, 'INbAllumageFailed');
				$EXT->event($DATA->DATA->IGNERRORS);
				$EXT->save();
				// mise a jour heure depuis entretien
				$EXT = $this->getCmd(null, 'IHeuresDepuisEntretien');
				$EXT->event($DATA->DATA->SERVICETIME);
				$EXT->save();
				// mise a jour quantité de pellets consommés
				$QUANT = $this->getCmd(null, 'IQuant');
				$QUANT->event($DATA->DATA->PQT);
				$QUANT->save();
			}
			// récupération de toutes les informations PalaControl
			$DATA = $this->makeRequest('GET+ALLS');
			if ($DATA != false) {
				// mise à jour température de consigne
				$TCON = $this->getCmd(null, 'IConsigne');
				$TCON->event($DATA->DATA->SETP);
				$TCON->save();
				// mise à jour status poele
				$STA = $this->getCmd(null, 'IStatus');
				$STA->event($DATA->DATA->STATUS);
				$STA->save();
				// mise à jour temperature ambiance
				$TMP = $this->getCmd(null, 'ITemp');
				$TMP->event($DATA->DATA->T1);
				$TMP->save();
				// mise a jour quantité de pellets consommée
				$QUANT = $this->getCmd(null, 'IQuant');
				$QUANT->event($DATA->DATA->PQT);
				$QUANT->save();
				// mise a jour variables snap
				$SNAP = $this->getCmd(null, 'ISnap');
				$SNAP->event(json_encode($DATA));
				$SNAP->save();
			}

			$DATA = $this->makeRequest('GET+POWR');
			if ($DATA != false) {
          		// mise à jour force du feu
				$POWR = $this->getCmd(null, 'IPower');
				$POWR->event($DATA->DATA->PWR);
				$POWR->save();
			}

			$DATA = $this->makeRequest('GET+FAND');
			if ($DATA != false) {
				// mise à jour force du ventilateur
				$FAN = $this->getCmd(null, 'IFan');
				$FAN->event($DATA->DATA->F2L);
				$FAN->save();
				// mise à jour force du ventilateur 3 F3L
				$FANF3L = $this->getCmd(null, 'IFanF3L');
				$FANF3L->event($DATA->DATA->F3L);
				$FANF3L->save();
				// mise à jour force du ventilateur 4 F4L
				$FANF4L = $this->getCmd(null, 'IFanF4L');
				$FANF4L->event($DATA->DATA->F4L);
				$FANF4L->save();
			}

			$DATA = $this->makeRequest('GET+TMPS');
			if ($DATA != false) {
				// mise à jour temperature ambiance
				$TMP = $this->getCmd(null, 'ITemp');
				$TMP->event($DATA->DATA->T1);
				$TMP->save();

				// mise à jour temperature granulés
				$TMP2 = $this->getCmd(null, 'ITemp2');
				$TMP2->event($DATA->DATA->T2);
				$TMP2->save();

				// mise à jour temperature granulés
				$TMP3 = $this->getCmd(null, 'ITemp3');
				$TMP3->event($DATA->DATA->T3);
				$TMP3->save();
			}
		}
	}
}

class PalazzettiCmd extends cmd
{


	/*     * *************************Attributs****************************** 
	public static $_widgetPossibility = array('custom' => false);

/*     * *********************Methode d'instance************************* */


	public function execute($_options = null)
	{

		$eqLogic 	= $this->getEqLogic();
		$idCmd 		= $this->getLogicalId();

		log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'options ' . json_encode($this->getConfiguration('options')));
		log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . '$_options ' . json_encode($_options));

		$eqLogic->sendCommand($this, $_options);
		$eqLogic->refreshWidget();
	}
}
