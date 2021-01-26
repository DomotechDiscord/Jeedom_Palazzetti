<?php
/*
 */
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Palazzetti extends eqLogic
{

    // tache automatique 15 minutes
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

    // apres creation equipement

    public static function cron15()
    {
        foreach (eqLogic::byType('Palazzetti') as $Palazzetti) {
            $Palazzetti->getInformations();
            $mc = cache::byKey('PalazzettiWidgetmobile' . $Palazzetti->getId());
            $mc->remove();
            $mc = cache::byKey('PalazzettiWidgetdashboard' . $Palazzetti->getId());
            $mc->remove();
            $Palazzetti->toHtml('mobile');
            $Palazzetti->toHtml('dashboard');
            $Palazzetti->refreshWidget();
            // mise à jour horloge
            //$date = date("Y-m-d H:i:s");
            //$DATA = $Palazzetti->makeRequest($cmdString) ;
        }
    }

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

    public function postInsert()
    {
        $TabCmd = array(
            'Time' => array('Libelle' => 'Time', 'actionCmd' => '', 'updateLogicalId' => '', 'LogicalId' => 'ITime', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'RTime' => array('Libelle' => 'Lire horodatage', 'actionCmd' => 'GET+TIME', 'updateLogicalId' => 'ITime', 'LogicalId' => 'RTime', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'Snap' => array('Libelle' => 'Informations', 'actionCmd' => '', 'updateLogicalId' => '', 'LogicalId' => 'ISnap', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'Network' => array('Libelle' => 'Réseau', 'actionCmd' => '', 'updateLogicalId' => '', 'LogicalId' => 'INetwork', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'WOn' => array('Libelle' => 'Allumage poêle', 'actionCmd' => 'CMD+ON', 'updateLogicalId' => 'IStatus', 'LogicalId' => 'WOn', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'WOff' => array('Libelle' => 'Extinction poêle', 'actionCmd' => 'CMD+OFF', 'updateLogicalId' => 'IStatus', 'LogicalId' => 'WOff', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'RStatus' => array('Libelle' => 'Lecture état poêle', 'actionCmd' => 'GET+STAT', 'updateLogicalId' => 'IStatus', 'LogicalId' => 'RStatus', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'Status' => array('Libelle' => 'Etat poêle', 'actionCmd' => '', 'updateLogicalId' => '', 'LogicalId' => 'IStatus', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'Name' => array('Libelle' => 'Nom', 'actionCmd' => '', 'updateLogicalId' => '', 'LogicalId' => 'IName', 'Type' => 'info', 'SubType' => 'string', 'visible' => 1, 'Unite' => '', 'Template' => ''),
            'WName' => array('Libelle' => 'Ecrire nom', 'actionCmd' => 'SET+LABL+', 'updateLogicalId' => 'IName', 'LogicalId' => 'WName', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RName' => array('Libelle' => 'Lire nom', 'actionCmd' => 'GET+LABL', 'updateLogicalId' => 'IName', 'LogicalId' => 'RName', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'WPower' => array('Libelle' => 'Ecrire force du feu', 'actionCmd' => 'SET+POWR+', 'updateLogicalId' => 'IPower', 'nparams' => 1, 'parameters' => '#slider#', 'minValue' => '1', 'maxValue' => '5', 'LogicalId' => 'WPower', 'Type' => 'action', 'SubType' => 'slider', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'Power' => array('Libelle' => 'Force du feu', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IPower', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'WConsigne' => array('Libelle' => 'Ecrire température de consigne', 'actionCmd' => 'SET+SETP+', 'updateLogicalId' => 'IConsigne', 'nparams' => 1, 'parameters' => '#slider#', 'minValue' => '0', 'maxValue' => '40', 'LogicalId' => 'WConsigne', 'Type' => 'action', 'SubType' => 'slider', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RConsigne' => array('Libelle' => 'Lire température de consigne', 'actionCmd' => 'GET+SETP', 'updateLogicalId' => 'IConsigne', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RConsigne', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'WFan' => array('Libelle' => 'Ecriture force ventilateur', 'actionCmd' => 'SET+RFAN+', 'updateLogicalId' => 'IFan', 'nparams' => 1, 'parameters' => '#slider#', 'minValue' => '0', 'maxValue' => '5', 'LogicalId' => 'WFan', 'Type' => 'action', 'SubType' => 'slider', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RFan' => array('Libelle' => 'Lire force ventilateur', 'actionCmd' => 'GET+FAND', 'updateLogicalId' => 'IFan', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RFan', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'Fan' => array('Libelle' => 'Force ventilateur', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IFan', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'WFanF3L' => array('Libelle' => 'Ecriture force ventilateur F3L', 'actionCmd' => 'SET+FN3L+', 'updateLogicalId' => 'IFanF3L', 'nparams' => 1, 'parameters' => '#slider#', 'minValue' => '0', 'maxValue' => '1', 'LogicalId' => 'WFanF3L', 'Type' => 'action', 'SubType' => 'slider', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RFanF3L' => array('Libelle' => 'Lire force ventilateur F3L', 'actionCmd' => 'GET+ALLS', 'updateLogicalId' => 'IFanF3L', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RFanF3L', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'FanF3L' => array('Libelle' => 'Force ventilateur F3L', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IFanF3L', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'WFanF4L' => array('Libelle' => 'Ecriture force ventilateur F4L', 'actionCmd' => 'SET+FN4L+', 'updateLogicalId' => 'IFanF4L', 'nparams' => 1, 'parameters' => '#slider#', 'minValue' => '0', 'maxValue' => '1', 'LogicalId' => 'WFanF4L', 'Type' => 'action', 'SubType' => 'slider', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RFanF4L' => array('Libelle' => 'Lire force ventilateur F4L', 'actionCmd' => 'GET+ALLS', 'updateLogicalId' => 'IFanF4L', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RFanF4L', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'FanF4L' => array('Libelle' => 'Force ventilateur F4L', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IFanF4L', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'visible' => 1, 'Template' => ''),
            'RTemp' => array('Libelle' => 'Lire température ambiance', 'actionCmd' => 'GET+TMPS', 'updateLogicalId' => 'ITemp', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RTemp', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'visible' => 0, 'Template' => ''),
            'WPH' => array('Libelle' => 'On/Off Programmes horaires', 'actionCmd' => 'SET+CSST+', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'WPH', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 1, 'Template' => ''),
            'RPH' => array('Libelle' => 'Lecture programmes horaires', 'actionCmd' => 'GET+CHRD', 'updateLogicalId' => 'IPH', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RPH', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'IPH' => array('Libelle' => 'Programmes horaires', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IPH', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 1, 'Template' => ''),
            'WPHtoDay' => array('Libelle' => 'Affectation programme horaire', 'actionCmd' => 'SET+CDAY+', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'WPHtoDay', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 1, 'Template' => ''),
            'WPHtranche' => array('Libelle' => 'Configuration tranche horaire', 'actionCmd' => 'SET+CPRD+', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'WPHtranche', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RNbAllumage' => array('Libelle' => 'Lecture nombre d\'allumages', 'actionCmd' => 'EXT+ADRD+2066+1', 'updateLogicalId' => 'INbAllumage', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RNbAllumage', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'INbAllumage' => array('Libelle' => 'Nombre d\'allumages', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'INbAllumage', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RNbAllumageFailed' => array('Libelle' => 'Lecture nombre d\'allumages échoués', 'actionCmd' => 'EXT+ADRD+207C+1', 'updateLogicalId' => 'INbAllumageFailed', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RNbAllumageFailed', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'INbAllumageFailed' => array('Libelle' => 'Nombre d\'allumages échoués', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'INbAllumageFailed', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RHeuresAlimElec' => array('Libelle' => 'Lecture heures alimentation électrique', 'actionCmd' => 'EXT+ADRD+206A+1', 'updateLogicalId' => 'IHeuresAlimElec', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RHeuresAlimElec', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'IHeuresAlimElec' => array('Libelle' => 'Nombre d\'heures alimentation électrique', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IHeuresAlimElec', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RHeuresChauffe' => array('Libelle' => 'Lecture heures de chauffe', 'actionCmd' => 'EXT+ADRD+2070+1', 'updateLogicalId' => 'IHeuresChauffe', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RHeuresChauffe', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'IHeuresChauffe' => array('Libelle' => 'Nombre d\'heures de chauffe', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IHeuresChauffe', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RHeuresSurChauffe' => array('Libelle' => 'Lecture heures de surchauffe', 'actionCmd' => 'EXT+ADRD+207A+1', 'updateLogicalId' => 'IHeuresSurChauffe', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RHeuresSurChauffe', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'IHeuresSurChauffe' => array('Libelle' => 'Nombre d\'heures de surchauffe', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IHeuresSurChauffe', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'RHeuresDepuisEntretien' => array('Libelle' => 'Lecture heures depuis entretien', 'actionCmd' => 'EXT+ADRD+2076+1', 'updateLogicalId' => 'IHeuresDepuisEntretien', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'RHeuresDepuisEntretien', 'Type' => 'action', 'SubType' => 'other', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'IHeuresDepuisEntretien' => array('Libelle' => 'Nombre d\'heures depuis entretien', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IHeuresDepuisEntretien', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => false, 'visible' => 0, 'Template' => ''),
            'Temp' => array('Libelle' => 'Température ambiance', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'ITemp', 'Type' => 'info', 'SubType' => 'numeric', 'Unite' => '', 'IsHistorized' => true, 'visible' => 1, 'Template' => ''),
            'Consigne' => array('Libelle' => 'Température de consigne', 'actionCmd' => '', 'updateLogicalId' => '', 'nparams' => 0, 'parameters' => '', 'minValue' => '', 'maxValue' => '', 'LogicalId' => 'IConsigne', 'Type' => 'info', 'SubType' => 'string', 'Unite' => '', 'IsHistorized' => true, 'visible' => 1, 'Template' => '')
        );

        //Chaque commande
        $Order = 0;
        foreach ($TabCmd as $CmdKey => $Cmd) {

            $PalazzettiCmd = $this->getCmd(null, $CmdKey);
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
                $PalazzettiCmd->setType($Cmd['Unite']);
            }
            if ($Cmd['IsHistorized'] == true) {
                $PalazzettiCmd->setIsHistorized(1);
            }
            $PalazzettiCmd->setOrder($Order);
            $PalazzettiCmd->save();
            $Order++;
        }
    }

    public function preUpdate()
    {
        // verification url disponible!!
    }

    // methode requete

    public function postUpdate()
    {
        foreach (eqLogic::byType('Palazzetti') as $Palazzetti) {
            $Palazzetti->getInformations();
        }
    }

    // interpretation valeur ventilateur

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
        if ($DATA->INFO->RSP != 'OK') {
            log::add('Palazzetti', 'error', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . ' erreur ' . $CMD . ' : ' . $DATA->INFO->RSP);
            return false;
        }
        // definition patern de comparaison
        $expl = explode('+', $cmdString);
        $pattern = $expl[0] . '+' . $expl[1];

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

        // mise a jour variables info
        if ($CMD->getConfiguration('updateLogicalId')) {
            $INFO = $this->getCmd(null, $CMD->getConfiguration('updateLogicalId'));
            $INFO->event($value);
            $INFO->save();
            log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'response ' . $value);
            log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'updatelogicalId ' . $CMD->getConfiguration('updateLogicalId') . ' = ' . $value);
        }
        // mise à jour lastvalue commande
        $CMD->setConfiguration('lastCmdValue', $value);
        $CMD->save();
        return 'OK';
    }

    public function makeRequest($cmd)
    {
        $ip = $this->getConfiguration('addressip');
        if ($ip != null && $ip != "") {
            try {
                $url = 'http://' . $ip . '/cgi-bin/sendmsg.lua?cmd=' . $cmd;
                log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'get URL ' . $url);

                $request_http = new com_http($url);
                $return = $request_http->exec(10);
                $return = json_decode($return);
                if ($return->INFO->RSP != 'OK') {
                    return false;
                } else {
                    return $return;
                }
            } catch (Exception $e) {
                log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'Exception reçue ' . $e->getMessage());
                return false;
            }
        } else {
            return false;
        }

    }

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

    // interpretation valeur status poele

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
        } else {
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

    // methode jour de la semaine

    public function toHtml($_version = 'dashboard')
    {
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

    // methode traitement commande

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

    // recuperation automatique des informations

    public function getInformations()
    {

        // recuperation del'heure
        $DATA = $this->makeRequest('GET+TIME');
        if ($DATA != false) {
            // mise à jour nom du poêle
            $TIME = $this->getCmd(null, 'ITime');
            $TIME->event(json_encode($DATA));
            $TIME->save();
        }

        // recuperation de toutes les informations réseau
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

        // recuperation des programmes horaires
        $DATA = $this->makeRequest('GET+CHRD');
        if ($DATA != false) {
            // mise à jour programmes horaires
            $PH = $this->getCmd(null, 'IPH');
            $PH->event(json_encode($DATA->DATA));
            $PH->save();
        }

        // recuperation des infos autoamte
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

        // recuperation de toutes les informations
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
    }
}

class PalazzettiCmd extends cmd
{


    /*     * *************************Attributs******************************
    public static $_widgetPossibility = array('custom' => false);

/*     * *********************Methode d'instance************************* */


    public function execute($_options = null)
    {

        $eqLogic = $this->getEqLogic();
        $idCmd = $this->getLogicalId();

        log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . 'options ' . json_encode($this->getConfiguration('options')));
        log::add('Palazzetti', 'debug', '(' . __LINE__ . ') ' . __FUNCTION__ . ' - ' . '$_options ' . json_encode($_options));

        $eqLogic->sendCommand($this, $_options);
        $eqLogic->refreshWidget();
    }
}
