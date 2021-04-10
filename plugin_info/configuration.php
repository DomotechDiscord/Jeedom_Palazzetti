<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
$plugin = plugin::byId('smartthings');

?>
<form class="form-horizontal">
    <fieldset>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Intervalle de rafraîchissement des informations (cron)}}<sup>
				<i class="fa fa-question-circle tooltips" title="{{Sélectionnez l'intervalle de récupération des informations.</br>Par défaut : 15 minute.}}"></i>
						</sup></label>
			<div class="col-lg-4">
				<select class="configKey form-control" data-l1key="autorefresh" >
					<option value="* * * * *">{{Toutes les minutes}}</option>
					<option value="*/2 * * * *">{{Toutes les 2 minutes}}</option>
					<option value="*/3 * * * *">{{Toutes les 3 minutes}}</option>
					<option value="*/5 * * * *">{{Toutes les 5 minutes}}</option>
					<option value="*/10 * * * *">{{Toutes les 10 minutes}}</option>
					<option value="*/15 * * * *">{{Toutes les 15 minutes}}</option>
					<option value="*/30 * * * *">{{Toutes les 30 minutes}}</option>
					<option value="*/45 * * * *">{{Toutes les 45 minutes}}</option>
					<option value="">{{Jamais}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Désactivation automatique de l'appareil}}
<sup><i class="fas fa-question-circle tooltipstered" title="{{Permet de désactiver l'équipement en cas de non réponse 3 fois consécutives.}}"></i></sup>
			</label>
			<div class="col-lg-4">
				<input type="checkbox" class="configKey form-control" data-l1key="autoDisable" >
				</input>
			</div>
		</div>
	</fieldset>
</form>
