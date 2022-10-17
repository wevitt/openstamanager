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

use Models\Group;
use Models\User;
use Modules\Anagrafiche\Anagrafica;

Permissions::check('rw');

$google = setting('Google Maps API key');
$token = auth()->getToken();
$user = Auth::user();
$anagrafica = Anagrafica::find($user->idanagrafica);
if (!empty($anagrafica->indirizzo)) {
    $indirizzo = $anagrafica->indirizzo . ', ' .  $anagrafica->citta;
} else {
    $indirizzo = "";
}
$modulo = Modules::get('Crea giro');
$url_crea_percorso = $modulo->fileurl('crea_percorso.php');
$url_crea_percorso_ottimizzato = $modulo->fileurl('crea_percorso_ottimizzato.php');
?>
<?php if (!empty($google)) { ?>
    <script src='https://maps.googleapis.com/maps/api/js?key=<?= $google ?>&libraries=places&solution_channel=GMP_QB_addressselection_v1_cABC'></script>
<?php } ?>

<form class="form-add">
    <input type="hidden" class="url_crea_percorso" value="<?= $url_crea_percorso ?>">
    <input type="hidden" class="url_crea_percorso_ottimizzato" value="<?= $url_crea_percorso_ottimizzato ?>">
    <input type="hidden" class="indirizzo" value="<?= $indirizzo ?>">

    <div class="row" style="margin-top:15px">
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Nome percorso'); ?>", "name": "nome_percorso", "required": 1 ]}
        </div>
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Note'); ?>", "name": "note", "required": 0 ]}
        </div>
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Punto di partenza'); ?>", "name": "punto_di_partenza", "required": 1 ]}
        </div>
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "<?php echo tr('Ritorno al punto di partenza?'); ?>", "name": "punto_di_arrivo", "value": "0" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "date", "label": "<?php echo tr('Data di partenza'); ?>", "name": "data_di_partenza", "required": 1 ]}
        </div>
        <div class="col-md-6">
            {[ "type": "time", "label": "<?php echo tr('Orario di partenza'); ?>", "name": "orario_di_partenza", "required": 1 ]}
        </div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="generaPercorso(true)">
                <i class="fa fa-plus"></i> <?php echo tr('Genera percorso ottimizzato con mappa');?>
            </button>
            <button type="button" class="btn btn-primary" onclick="generaPercorso(false)">
                <i class="fa fa-plus"></i> <?php echo tr('Genera percorso');?>
            </button>
        </div>
    </div>
</form>

<script>
	$(document).ready(function() {
        var $date = new Date();
        var indirizzo = $('.indirizzo').val();

        $("[name='data_di_partenza']").val($date.getDate());
        $("[name='orario_di_partenza']").val($date.getHours() + ":" + $date.getMinutes());
        $("[name='punto_di_partenza']").val(indirizzo);
    });

    function generaPercorso($withMaps) {
        var url = ($withMaps) ? $('.url_crea_percorso_ottimizzato').val() : $('.url_crea_percorso').val();

        if (checkInputs()) {
            var data = $('.form-add').serialize();
            data = data.split("&");
            data[0] = "id_module=93";
            data = data.join("&");

            openModal(
                "Crea percorso",
                url + "?" + data
            );
        }
    }

    function checkInputs() {
        $('.is-invalid').removeClass("is-invalid");

        var flag = true;

        if (!$("[name='nome_percorso']").val()) {
            $("[name='nome_percorso']").addClass("is-invalid");
            flag = false;
        }
        if (!$("[name='punto_di_partenza']").val()) {
            $("[name='punto_di_partenza']").addClass("is-invalid");
            flag = false;
        }
        if (!$("[name='data_di_partenza']").val()) {
            $("[name='data_di_partenza']").addClass("is-invalid");
            flag = false;
        }
        if (!$("[name='orario_di_partenza']").val()) {
            $("[name='orario_di_partenza']").addClass("is-invalid");
            flag = false;
        }

        return flag;
    }

    function autocompleteIndirizzo() {
        const autocompleteInput = document.querySelector('[name=autocomplete-address]');
        const autocomplete = new google.maps.places.Autocomplete(autocompleteInput, {
            fields: ["address_components", "geometry", "name"],
            types: [ "establishment", "geocode"],
        });
        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (!place.geometry) {
                window.alert('Indirizzo sconosciuto: \'' + place.name + '\'');
                return;
            }
            //renderAddress(place);
            fillInAddress(place);
        });


        function fillInAddress(place) {
            var street_number = '';
            var route = '';
            var postal_code = '';
            var locality = '';

            for (var i = 0; i < place.address_components.length; i++) {
                var addressType = place.address_components[i].types[0];
                if (addressType == 'street_number') {
                    street_number = ', ' + place.address_components[i]['long_name'];
                }
                if (addressType == 'route') {
                    route = place.address_components[i]['long_name'];
                }
                if (addressType == 'locality') {
                    locality = place.address_components[i]['long_name'];
                }
                if (addressType == 'postal_code') {
                    postal_code = place.address_components[i]['long_name'];
                }
                if (addressType == 'administrative_area_level_2') {
                    provincia = place.address_components[i]['short_name'];
                }
                if (addressType == 'administrative_area_level_3') {
                    administrative_area_level_3 = place.address_components[i]['long_name'];
                }
                if (addressType == 'country') {
                    country = place.address_components[i]['long_name'];
                }
            }

            if (route === '') {
                route = place.name;
            }
            if (locality === '') {
                locality = administrative_area_level_3;
            }

            var placeName = place.name || '';
            var indirizzo = route + street_number + ", " + locality + ", " + provincia;

            $('[name="autocomplete-address"]').val(indirizzo);
        }

        /*function renderAddress(place) {
            map.setZoom(15);
            map.setCenter(place.geometry.location);
            marker.setPosition(place.geometry.location);
            marker.setVisible(true);
            $('#map').slideDown();
        }*/
    }
</script>

<style>
    .is-invalid {
        border: 1px solid #f00;
    }
</style>
