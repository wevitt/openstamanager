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

$id = $record['id'];
$spedizioni = $dbo->fetchArray('SELECT * FROM `sp_spedizioni_dettaglio` WHERE id_spedizione = '. prepare($id));
?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

    <div style="display:none" class="percorso"><?= $record['percorso'] ?></div>

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

        <div class="panel-body">
            <div style="margin-bottom:15px" class="col-12">
                <div id="map" style="height:400px;"></div>
            </div>

			<div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome percorso'); ?>", "name": "nome_percorso", "readonly": 1, "value": "$nome_percorso$" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Punto di partenza'); ?>", "name": "punto_di_partenza", "readonly": 1, "value": "$punto_di_partenza$" ]}
                </div>
                <div class="col-md-6">
                {[ "type": "date", "label": "<?php echo tr('Data di partenza'); ?>", "name": "data_di_partenza", "readonly": 1, "value": "$data_di_partenza$" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "time", "label": "<?php echo tr('Orario di partenza'); ?>", "name": "orario_di_partenza", "readonly": 1, "value": "$orario_di_partenza$" ]}
                </div>
                <div class="col-md-12">
                {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "readonly": 1, "value": "$note$" ]}
                </div>
			</div>

            <table class="tbl-riepilogo-percorso table-rate table table-hover table-striped" style="padding:12px;">
                <thead>
                    <tr>
                        <th><?php echo tr('Ord'); ?></th>
                        <th><?php echo tr('Ragione sociale'); ?></th>
                        <th><?php echo tr('Numero documento'); ?></th>
                        <th><?php echo tr('Colli'); ?></th>
                        <th><?php echo tr('Indirizzo'); ?></th>
                        <th><?php echo tr('Città'); ?></th>
                        <th><?php echo tr('Tempo'); ?></th>
                        <th><?php echo tr('Arrivo'); ?></th>
                        <th><?php echo tr('Km'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spedizioni as $spedizione) { ?>
                        <tr>
                            <td class="ord"><?= $spedizione['ordinamento'] ?></td>
                            <td class="ragione-sociale"><?= $spedizione['ragione_sociale'] ?></td>
                            <td class="numero-documento"><?= $spedizione['numero_documento'] ?></td>
                            <td class="colli"><?= $spedizione['colli'] ?></td>
                            <td class="indirizzo"><?= $spedizione['indirizzo'] ?></td>
                            <td class="citta"><?= $spedizione['citta'] ?></td>
                            <td class="tempo"><?= $spedizione['tempo'] ?></td>
                            <td class="arrivo"><?= $spedizione['orario_di_arrivo'] ?></td>
                            <td class="km"><?= $spedizione['km_percorsi'] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="col-xs-offset-8 col-xs-4">
                <table class="table-rate table table-hover">
                    <tr class="table-primary">
                        <td><?php echo tr('Tempo totale:'); ?></td>
                        <td class="tempo-totale"><?= $record['tempo_totale'] ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td><?php echo tr('Km totali:'); ?></td>
                        <td class="km-totali"><?= $record['km_totali'] ?></td>
                    </tr>
                </table>
            </div>
		</div>



	</div>

</form>

<script>
	$(document).ready( function() {
        var percorso = JSON.parse($('.percorso').html());
        initMap(percorso);
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


        /*const myHandler = function () {
            calculateAndDisplayRoute(directionsService, directionsRenderer);
        };
        $('.btn-crea-giro').on("click", myHandler);*/
    }
</script>
