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

$google = setting('Google Maps API key');

$id = $record['id'];
$spedizioni = $dbo->fetchArray('SELECT * FROM `sp_spedizioni_dettaglio` WHERE id_spedizione = '. prepare($id));
$modulo = Modules::get('Storico spedizioni');
$url = $modulo->fileurl('modal_riconsegna.php');
?>

<?php if (!empty($google)) { ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCYGCUb4fZaAm-RO_CcRXViXpxVBAecj8I"></script>
<?php } ?>

<input type="hidden" class="google-key" value="<?= $google ?>"/>

<form action="" method="post" id="edit-form">
    <input type="hidden" class="url" value="<?= $url ?>">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

    <div style="display:none" class="percorso"><?= $record['percorso'] ?></div>
    <input type="hidden" name="id_module" value="<?= filter('id_module') ?>">
    <input type="hidden" name="id_record" value="<?= filter('id_record') ?>">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

        <div class="panel-body">
            <?php if (!empty($google)) { ?>
                <div style="margin-bottom:15px" class="col-12">
                    <div id="map" style="height:400px;"></div>
                </div>
            <?php } ?>

            <!--s<div style="margin-bottom:15px" class="col-12">
                <img class="my-img" id="map2" style="height:400px;"/>
            </div>-->

			<div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome percorso'); ?>", "name": "nome_percorso", "readonly": 0, "value": "$nome_percorso$" ]}
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
                {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "readonly": 0, "value": "$note$" ]}
                </div>
			</div>

            <table class="tbl-riepilogo-percorso table-rate table table-hover table-striped" style="padding:12px;">
                <thead>
                    <tr>
                        <th class="ord"><?php echo tr('Ord'); ?></th>
                        <th><?php echo tr('Ragione sociale'); ?></th>
                        <th><?php echo tr('Numero documento'); ?></th>
                        <th><?php echo tr('Colli'); ?></th>
                        <th><?php echo tr('Indirizzo'); ?></th>
                        <th><?php echo tr('Città'); ?></th>
                        <th class="tempo"><?php echo tr('Tempo'); ?></th>
                        <th class="arrivo"><?php echo tr('Arrivo'); ?></th>
                        <th class="km"><?php echo tr('Km'); ?></th>
                        <th><?php echo tr('Azioni'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spedizioni as $spedizione) { ?>
                        <tr data-id=<?= $spedizione['id'] ?>>
                            <td class="ord"><?= $spedizione['ordinamento'] ?></td>
                            <td class="ragione-sociale"><?= $spedizione['ragione_sociale'] ?></td>
                            <td class="numero-documento"><?= $spedizione['numero_documento'] ?></td>
                            <td class="colli"><?= $spedizione['colli'] ?></td>
                            <td class="indirizzo"><?= $spedizione['indirizzo'] ?></td>
                            <td class="citta"><?= $spedizione['citta'] ?></td>
                            <td class="tempo"><?= $spedizione['tempo'] ?></td>
                            <td class="arrivo"><?= $spedizione['orario_di_arrivo'] ?></td>
                            <td class="km"><?= $spedizione['km_percorsi'] ?></td>
                            <td class="azioni"><a class="btn" onclick="riconsegna($(this))">
                                <input type="hidden" class="stato" value="<?= $spedizione['stato'] ?>">
                                <i style="<?= ($spedizione['stato'] == 0) ? 'color:green' : 'color:red'; ?>" class="fa fa-truck"></i></a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="total-data col-xs-offset-8 col-xs-4">
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

            <a class="btn btn-danger ask" onclick="elimina()" data-backto="record-list">
                <i class="fa fa-trash"></i> <?= tr('Elimina') ?>
            </a>
		</div>
	</div>
</form>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.2/jspdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
<script>
	$(document).ready( function() {
        var percorso = $('.percorso').html();
        var googleKey = $('.google-key').val();

        if (googleKey != "" && percorso) {
            percorso = JSON.parse(percorso);
            initMap(percorso);

            /*let div = $('#map');//.html();

            html2canvas(div, {
                useCORS: true,
                onrendered: function(canvas) {
                    var img =canvas.toDataURL("image/jpeg,1.0");
                    console.log(img);
                    $('.my-img').attr('src', img);
                }
            });*/

           // var dataUrl = document.getElementById('anycanvas').toDataURL(); //attempt to save base64 string to server using this var


        } else {
            $('#map').css('display', 'none');
            $('[name="data_di_partenza"]').closest('div').css('display', 'none');
            $('[name="orario_di_partenza"]').closest('div').css('display', 'none');
            $('.total-data').css('display', 'none');
            $('.ord').css('display', 'none');
            $('.tempo').css('display', 'none');
            $('.arrivo').css('display', 'none');
            $('.km').css('display', 'none');
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


        /*const myHandler = function () {
            calculateAndDisplayRoute(directionsService, directionsRenderer);
        };
        $('.btn-crea-giro').on("click", myHandler);*/
    }

    function riconsegna($this) {
        var url = $('.url').val();
        var stato = $this.find('input').val();

        var data =
            "id_module=" + $('[name="id_module"]').val() +
            "&id_record=" + $('[name="id_record"]').val() +
            "&id_consegna=" + $this.closest('tr').data('id') +
            "&stato=" + stato;

        openModal(
            "Stato consegna",
            url + "?" + data
        );
    }

    function elimina() {
        $('[name="op"]').val('delete');
        //$("#edit-form").submit();
    }
</script>
