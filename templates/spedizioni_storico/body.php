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
if ($record) {
    $spedizioni = $dbo->fetchArray('SELECT * FROM `sp_spedizioni_dettaglio` WHERE `id_spedizione` = ' . prepare($record['id']));
}

echo
'<div style="display:none" class="percorso">' . $record['percorso'] . '</div>

<!--<div style="margin-bottom:15px; margin-top:15px;" class="col-12">
    <div id="map" style="height:400px;"></div>
</div>-->

<table class="table table-bordered">
    <tr>
        <th colspan="12" style="font-size:13pt;" class="text-center">'.tr('Dettaglio spedizione', [], ['upper' => true]).'</th>
    </tr>
    <tr>
        <td colspan="12" class="text-left" >'.tr('Nome percorso').': <b>'.$record['nome_percorso'].'</b></td>
    </tr>
    <tr>
        <td colspan="12" class="text-left" >'.tr('Punto di partenza').': <b>'.$record['punto_di_partenza'].'</b></td>
    </tr>
    <tr>
        <td colspan="12" class="text-left" >'.tr('Data di partenza').': <b>'.$record['data_di_partenza'].'</b></td>
    </tr>
    <tr>
        <td colspan="12" class="text-left" >'.tr('Orario di partenza').': <b>'.$record['orario_di_partenza'].'</b></td>
    </tr>
    <tr>
        <td colspan="12" class="text-left" >'.tr('Note').': <b>'.$record['note'].'</b></td>
    </tr>
</table>

<table class="table table-bordered">
    <thead>
        <tr>
            <th class="ord">' . tr('Ord') . '</th>
            <th>' .  tr('Ragione sociale') . '</th>
            <th>' .  tr('Numero documento') . '</th>
            <th>' .  tr('Colli') . '</th>
            <th>' .  tr('Indirizzo') . '</th>
            <th>' .  tr('Città') . '</th>
            <th class="tempo">' .  tr('Tempo') . '</th>
            <th class="arrivo">' .  tr('Arrivo') . '</th>
            <th class="km">' .  tr('Km') . '</th>
        </tr>
    </thead>
    <tbody>';
        foreach ($spedizioni as $spedizione) {
            echo '
            <tr>
                <td class="ord">' .  $spedizione['ordinamento']  . '</td>
                <td class="ragione-sociale">' .  $spedizione['ragione_sociale']  . '</td>
                <td class="numero-documento">' .  $spedizione['numero_documento']  . '</td>
                <td class="colli">' .  $spedizione['colli']  . '</td>
                <td class="indirizzo">' .  $spedizione['indirizzo']  . '</td>
                <td class="citta">' .  $spedizione['citta']  . '</td>
                <td class="tempo">' .  $spedizione['tempo']  . '</td>
                <td class="arrivo">' .  $spedizione['orario_di_arrivo']  . '</td>
                <td class="km">' .  $spedizione['km_percorsi']  . '</td>
            </tr>';
        }
    echo '
    </tbody>
</table>

<div class="total-data col-xs-offset-8 col-xs-4">
    <table class="table table-bordered">
        <tr class="table-primary">
            <td>' .  tr('Tempo totale:') . '</td>
            <td class="tempo-totale">' .  $record['tempo_totale']  . '</td>
        </tr>
        <tr class="table-primary">
            <td>' .  tr('Km totali:') . '</td>
            <td class="km-totali">' .  $record['km_totali']  . '</td>
        </tr>
    </table>
</div>';
?>

<script>
	$(document).ready( function() {
        var percorso = $(".percorso").html();
        if (percorso) {
            percorso = JSON.parse(percorso);
            $("#map").css("display", "none");

            //initMap(percorso);
        } else {
            $("#map").css("display", "none");
        }
	});

    function initMap(percorso) {
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 12,
            center: { lat: 44.7124214, lng: 8.0330452 },
        });

        directionsRenderer.setMap(map);
        directionsRenderer.setDirections(percorso);
    }
</script>
