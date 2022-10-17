<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

$id_anagrafica = $chiamata->anagrafica->id;

// Interventi selezionabili
$interventi = $database->fetchArray("SELECT
        in_interventi.id,
        CONCAT('Intervento numero ', in_interventi.codice, ' del ', DATE_FORMAT(IFNULL((SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), in_interventi.data_richiesta), '%d/%m/%Y'), ' [', `in_statiintervento`.`descrizione` , ']') AS descrizione,
        IF(idclientefinale=".prepare($id_anagrafica).", 'Interventi conto terzi', 'Interventi diretti') AS `optgroup`
    FROM
        in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento
    WHERE
        (in_interventi.idanagrafica=".prepare($id_anagrafica).' OR in_interventi.idclientefinale='.prepare($id_anagrafica).')
        AND in_statiintervento.is_completato = 0');

echo '
<p>'.tr("Selezione l'attività a cui associate la chiamata").'.</p>

<form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="associa_intervento">
    <input type="hidden" name="backto" value="record-edit">';

// Intervento
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Intervento').'", "name": "id_intervento", "required": 1, "values": '.json_encode($interventi).' ]}
        </div>

		<div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Aggiungi chiamata').'", "name": "aggiungi_chiamata", "placeholder": "'.tr('Aggiungi una sessione di lavoro corrispondente alla chiamata').'.", "value": "'.(empty($chiamata->tecnico) ? 0 : 1).'", "disabled": "'.(empty($chiamata->tecnico) ? 1 : 0).'", "help": "'.(empty($chiamata->tecnico) ? tr('Impossibile aggiungere la sessione di lavoro prima della conclusione della chiamata') : '').'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Salva').'
			</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';
