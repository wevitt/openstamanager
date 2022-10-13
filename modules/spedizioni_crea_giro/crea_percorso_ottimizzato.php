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

$nome_percorso = filter('nome_percorso');
$note = filter('note');
$punto_di_partenza = filter('punto_di_partenza');
$punto_di_arrivo = filter('punto_di_arrivo');
$data_di_partenza = filter('data_di_partenza');
$orario_di_partenza = filter('orario_di_partenza');

if (empty($google)) {
    echo
    '<div class="alert alert-info">
        '.Modules::link('Impostazioni', null, tr('Per abilitare la visualizzazione delle anagrafiche nella mappa, inserire la Google Maps API Key nella scheda Impostazioni'), true, null, true, null, '&search=Google Maps API key').'.
    </div>';
}
?>

<input type="hidden" class="google-key" value="<?= $google ?>"/>

<?php if (!empty($google)) { ?>
    <form action="" method="post" id="add-form">
        <input type="hidden" name="op" value="add">
        <input type="hidden" name="backto" value="record-list">

        <input type="hidden" name="percorso" class="percorso">
        <input type="hidden" name="note" class="note" value="<?= $note ?>">
        <input type="hidden" class="punto_di_arrivo" value="<?= $punto_di_arrivo ?>">

        <div id="map" style="height:400px;" class="col-12"></div>

        <div class="row" style="margin-top:15px">
            <div class="col-md-6">
                {[ "type": "text", "label": "<?php echo tr('Nome percorso'); ?>", "name": "nome_percorso", "readonly": 1, "value":"<?= $nome_percorso ?>" ]}
            </div>
            <div class="col-md-6">
                {[ "type": "text", "label": "<?php echo tr('Punto di partenza'); ?>", "name": "punto_di_partenza", "readonly": 1, "value":"<?= $punto_di_partenza ?>" ]}
            </div>
            <div class="col-md-6">
                {[ "type": "date", "label": "<?php echo tr('Data di partenza'); ?>", "name": "data_di_partenza", "readonly": 1, "value":"<?= $data_di_partenza ?>" ]}
            </div>
            <div class="col-md-6">
                {[ "type": "time", "label": "<?php echo tr('Orario di partenza'); ?>", "name": "orario_di_partenza", "readonly": 1, "value":"<?= $orario_di_partenza ?>" ]}
            </div>
        </div>

        <table class="tbl-percorso table-rate table table-hover table-striped">
            <thead>
                <tr>
                    <th style="display:none"><?php echo tr('Numero'); ?></th>
                    <th><?php echo tr('Ord'); ?></th>
                    <th><?php echo tr(''); ?></th>
                    <th><?php echo tr(''); ?></th>
                    <th><?php echo tr(''); ?></th>
                    <th><?php echo tr('Ragione sociale'); ?></th>
                    <th><?php echo tr('Indirizzo'); ?></th>
                    <th><?php echo tr('Città'); ?></th>
                    <th><?php echo tr('Tempo'); ?></th>
                    <th><?php echo tr('Arrivo'); ?></th>
                    <th><?php echo tr('Km'); ?></th>
                    <th><?php echo tr('Colli'); ?></th>
                    <th><?php echo tr('Azioni'); ?></th>
                    <th style="display:none"></th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot style="display: none">
                <tr>
                    <td class="numero-documento" style="display:none"></td>
                    <td class="ord"></td>
                    <td class="up" onclick="moveUp($(this))" style="cursor:pointer"><i class="fa fa-arrow-up" aria-hidden="true"></i></td>
                    <td class="down" onclick="moveDown($(this))" style="cursor:pointer"><i class="fa fa-arrow-down" aria-hidden="true"></i></td>
                    <td class="padlock text-muted" onclick="padlock($(this))" style="cursor:pointer"><i class="fa fa-unlock-alt" aria-hidden="true"></i></td>
                    <td class="ragione-sociale"></td>
                    <td class="indirizzo"></td>
                    <td class="citta"></td>
                    <td class="cap" style="display:none"></td>
                    <td class="provincia" style="display:none"></td>
                    <td class="tempo"></td>
                    <td class="arrivo"></td>
                    <td class="km"></td>
                    <td class="colli"></td>
                    <td class="azioni"></td>
                    <td class="inputs" style="display:none">
                        <input type="hidden" name="id[]">
                        <input type="hidden" name="tipo_consegna[]">
                        <input type="hidden" name="numero_documento[]">
                        <input type="hidden" name="ragione_sociale[]">
                        <input type="hidden" name="indirizzo[]">
                        <input type="hidden" name="citta[]">
                        <input type="hidden" name="cap[]">
                        <input type="hidden" name="provincia[]">
                        <input type="hidden" name="tempo[]">
                        <input type="hidden" name="arrivo[]">
                        <input type="hidden" name="km[]">
                        <input type="hidden" name="colli[]">
                        <input type="hidden" name="ord[]">
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="col-xs-offset-8 col-xs-4" style="margin-bottom:15px;">
            <table class="table-rate table table-hover">
                <tr class="table-primary">
                    <td><?php echo tr('Tempo totale:'); ?></td>
                    <td class="tempo-totale"></td>
                    <input type="hidden" name="tempo_totale"/>
                </tr>
                <tr class="table-primary">
                    <td><?php echo tr('Km totali:'); ?></td>
                    <td class="km-totali"></td>
                    <input type="hidden" name="km_totali"/>
                </tr>
            </table>
        </div>


        <!-- PULSANTI -->
        <div class="row">
            <div class="col-md-12 text-right">
                <a class="btn btn-primary btn-aggiungi-tappa" onclick="aggiungiTappa()"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi tappa'); ?></a>
                <a class="btn btn-primary btn-riordina-giro" disabled onclick="riordinaGiro()"><?php echo tr('Riordina giro'); ?></a>
                <a class="btn btn-primary btn-ricalcola-giro" disabled onclick="ricalcolaGiro()"><?php echo tr('Ricalcola giro'); ?></a>

                <button type="submit" class="btn btn-primary"><?php echo tr('Salva'); ?></button>
            </div>
        </div>
    </form>
<?php } ?>


<script>
    var directionsService;
    var directionsRenderer;
    var waypts = [];

    $(document).ready(function() {
        var googleKey = $('.google-key').val();

        if (googleKey != "") {
            initMap();
        }
    });

    function initMap() {
        directionsService = new google.maps.DirectionsService();
        directionsRenderer = new google.maps.DirectionsRenderer();
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 12,
            center: { lat: 44.7124214, lng: 8.0330452 },
        });

        directionsRenderer.setMap(map);

        firstCalculation(directionsService, directionsRenderer);
    }

    function firstCalculation(directionsService, directionsRenderer) {
        var result = loadTappe();
        var waypts = result['waypts'];
        var tappe = result['tappe'];

        calculateAndDisplayRoute(directionsService, directionsRenderer, waypts, tappe, true, true, false);
    }

    function riordinaGiro() {
        if ($('.btn-riordina-giro').attr('disabled') == undefined) {
            var result = loadCurrentTappe(false);
            var waypts = result['waypts'];
            var tappe = result['tappe'];
            var isPartial = result['isPartial'];

            calculateAndDisplayRoute(directionsService, directionsRenderer, waypts, tappe, true, false, isPartial);
            disableRiordinaGiro(true);
            disableRicalcolaGiro(true);
        }
    }

    function ricalcolaGiro() {
        if ($('.btn-ricalcola-giro').attr('disabled') == undefined) {
            var result = loadCurrentTappe(false);
            var waypts = result['waypts'];
            var tappe = result['tappe'];
            var isPartial = result['isPartial'];

            calculateAndDisplayRoute(directionsService, directionsRenderer, waypts, tappe, false, false, isPartial);
            disableRicalcolaGiro(true);
        }
    }

    function aggiungiTappa() {
        if ($('.btn-aggiungi-tappa').attr('disabled') == undefined) {
            disableRiordinaGiro(true);
            disableRicalcolaGiro(true);
            disableAggiungiTappa(true);

            var $table = $(".tbl-percorso");
            var $body = $table.find('tbody');
            var $template = $table.find('tfoot');

            $template.find('.numero-documento').text('');
            $template.find('.ord').text('');
            $template.find('.ragione-sociale').text('');
            $template.find('.indirizzo').html('<input type="text" class="form-control" name="autocomplete-address"/>');
            $template.find('.citta').html('');
            $template.find('.cap').text('');
            $template.find('.provincia').text('');
            $template.find('.tempo').text('');
            $template.find('.arrivo').text('');
            $template.find('.km').text('');
            $template.find('.colli').text('');
            $template.find('.azioni').html(
                    '<a class="btn" onclick="inserisciTappa($(this))"><i class="fa fa-check" aria-hidden="true"></i></a>' +
                    '<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>'
                );
            $inputs = $template.find('.inputs');
            $inputs.find('[name="id[]"]').val('');
            $inputs.find('[name="numero_documento[]"]').val('');
            $inputs.find('[name="tipo_consegna[]"]').val('');
            $inputs.find('[name="ragione_sociale[]"]').val('');
            $inputs.find('[name="indirizzo[]"]').val('');
            $inputs.find('[name="citta[]"]').val('');
            $inputs.find('[name="cap[]"]').val('');
            $inputs.find('[name="provincia[]"]').val('');
            $inputs.find('[name="tempo[]"]').val('');
            $inputs.find('[name="arrivo[]"]').val('');
            $inputs.find('[name="km[]"]').val('');
            $inputs.find('[name="colli[]"]').val('');
            $inputs.find('[name="ord[]"]').val('');

            $body.append($template.html());
            autocompleteIndirizzo();
        }
    }

    function rimuoviTappa($this) {
        var $row = $this.closest('tr');
        $row.remove();


        disableRiordinaGiro(false);
        disableRicalcolaGiro(false);
        disableAggiungiTappa(false);
    }

    function inserisciTappa($this) {
        $('.btn-riordina-giro').prop('disabled', false);
        $('.is-invalid').removeClass("is-invalid");
        var $row = $this.closest('tr');

        var indirizzo = $row.find('.indirizzo input').val();
        if (!indirizzo) {
            $row.find('.indirizzo input').addClass('is-invalid');
        } else {
            var array = indirizzo.split(',');
            if (array.length < 4) {
                $row.find('.indirizzo input').addClass('is-invalid');
            } else {
                var i = 0
                if (array.length == 5) { //è indicato anche il numero civico
                    $row.find('.indirizzo').text(array[i] + ' ' + array[i+1]);
                    i++;
                } else {
                    $row.find('.indirizzo').text(array[i]);
                }
                $row.find('.citta').text(array[i+1]);
                $row.find('.cap').text(array[i+2]);
                $row.find('.provincia').text(array[i+2]);
                $row.find('.azioni').html('<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>');

                disableRiordinaGiro(false);
                disableRicalcolaGiro(false);
                disableAggiungiTappa(false);
            }
        }
    }

    function padlock($this) {
        var $table = $(".tbl-percorso");
        var $rows = $table.find("tbody tr");

        var $cell = $this.closest('td');
        var $currentRow = $this.closest('tr');
        var rowNumber = $currentRow.index();

        var i = 0;
        if ($cell.hasClass('text-muted')) { //from unlock to lock
            while (i <= rowNumber) {
                $item = $($rows[i]);
                $padlock = $item.find('.padlock');

                $item.addClass('bg-gray');
                $padlock.removeClass('text-muted');
                $padlock.addClass('text-warning');
                $padlock.html('<i class="fa fa-lock" aria-hidden="true"></i>');

                i++;
            }

        } else { //from lock to unlock
            while (i <= $rows.length) {
                $item = $($rows[i]);
                $padlock = $item.find('.padlock');

                $item.removeClass('bg-gray');
                $padlock.removeClass('text-warning');
                $padlock.addClass('text-muted');
                $padlock.html('<i class="fa fa-unlock-alt" aria-hidden="true"></i>');

                i++;
            }
        }
    }

    function composePercorso(response, tappeUnlocked) { //TODO: caso tutti blocatti
        var route = response.routes[0];

        //ordino i waypts (to do: controllare se calcolo e optimized o no)
        var tappe_copia = [];
        var wayptsUnlocked = [];
        for (var i = 0; i < route.waypoint_order.length; i++){
            tappe_copia[i] = tappeUnlocked[route.waypoint_order[i] + 1];
            wayptsUnlocked.push({
                location: tappe_copia[i].indirizzo + ", " + tappe_copia[i].citta,
                stopover: true,
            });
        }

        //inserisco la destinazione finale in tappe (to do: controllare da dove viene prese l'end)
        tappe_copia[tappe_copia.length] = tappeUnlocked[tappeUnlocked.length-1];
        wayptsUnlocked.push({
            location: tappe_copia[tappe_copia.length-1].indirizzo + ", " + tappe_copia[tappe_copia.length-1].citta,
            stopover: true,
        });

        tappeUnlocked = tappe_copia;

        //prendo tappe bloccate e le unisco a quelle non bloccate ordinate
        var result = loadCurrentTappe(true);
        var wayptsLocked = result['waypts'];
        var tappeLocked = result['tappe'];

        var waypts = $.merge(wayptsLocked, wayptsUnlocked)
        var tappe = $.merge(tappeLocked, tappeUnlocked)

        calculateAndDisplayRoute(directionsService, directionsRenderer, waypts, tappe, false, false, false);
    }

    function calculateAndDisplayRoute(directionsService, directionsRenderer, waypts, tappe, isOptimized, firstTime, isPartial)  {
        var start;
        var end;

        if (!isPartial) {
            start = $('[name=punto_di_partenza]').val();
        } else {
            start = (waypts.shift()).location
        }

        if ($('.punto_di_arrivo').val() && firstTime) { //firstTime perchè nei calcoli successivi l'ultima tappa sarà l'ultima riga della tbl
            end = $('[name=punto_di_partenza]').val();
        } else {
            end = (waypts.pop()).location;
        }

        //orario_di_partenza
        var date = $('[name="data_di_partenza"]').val().split('/');
        var dd = date[0];
        var mm = date[1];
        var yyyy = date[2];
        today = mm + '/' + dd + '/' + yyyy;
        date = new Date(today + " " + $('[name="orario_di_partenza"]').val());

        directionsService
        .route({
            origin: { query: start },
            destination: { query: end },
            waypoints: waypts,
            optimizeWaypoints: isOptimized,
            travelMode: google.maps.TravelMode.DRIVING,
            drivingOptions: {
                departureTime: date,
                trafficModel: 'pessimistic'
            },
        })
        .then((response) => {
            //mappa la risposta
            if (isPartial) { //se parziale unisco alle tappe rimanenti e ricalcolo il percorso (non ottimizzato)
                composePercorso(response, tappe);
            } else {
                directionsRenderer.setDirections(response);
                setPercorso(response, tappe, date, firstTime);
            }
        })
        .catch((e) => {
            console.log(e);
            switch (e.code) {
                case "MAX_WAYPOINTS_EXCEEDED":
                    alert("Attenzione, troppi indirizzi inseriti, puoi inserirne al massimo 25");
                    break;
                case "ZERO_RESULTS":
                    alert("Attenzione, non è stato possibile calcolare il percorso");
                    break;
                case "NOT_FOUND":
                    alert("Attenzione, non è stato trovato l'indirizzo");
                    break;
                default:
                    alert("Attenzione, errore nei dati inseriti");
                    break;
            }
        });
    }

    function setPercorso(response, tappe, date, firstTime) {
        //disableButtons(true);
        $('.percorso').val(JSON.stringify(response));

        var route = response.routes[0];
        var total_distance = 0.0;
        var total_hours = 0;
        var total_min = 0;
        var duration;

        //ordino i waypts (to do: controllare se calcolo e optimized o no)
        var tappe_copia = [];
        for (var i = 0; i < route.waypoint_order.length; i++){
            tappe_copia[i] = tappe[route.waypoint_order[i]];
        }

        //inserisco la destinazione finale in tappe (to do: controllare da dove viene prese l'end)
        if ($('.punto_di_arrivo').val() && firstTime) {
            var array = (route.legs[route.legs.length-1].end_address).split(",");
            if (array.length > 2) {
                if (array.length == 3) { //manca numero civico
                    var subarray = array[1].split(" ");
                    var indirizzo = array[0]
                } else { //indirizzo completo
                    var subarray = array[2].split(" ");
                    var indirizzo = array[0] + "," + array[1];
                }
                subarray.shift();
                var cap = subarray.shift();
                var provincia = subarray.pop();
                var citta = subarray.join(" ");
            } else {
                var indirizzo = array.join(" ");
                var cap = "";
                var provincia = "";
                var citta = "";
            }

            tappe_copia[tappe_copia.length] = {
                id: "",
                numero: "",
                tipo_consegna: "",
                ragione_sociale: "",
                indirizzo: indirizzo,
                citta: citta,
                cap: cap,
                provincia: provincia,
                colli: "",
            }
        } else {
            tappe_copia[tappe_copia.length] = tappe[tappe.length-1];
        }

        tappe = tappe_copia;

        //conteggio km e durata
        for (var i=0; i<route.legs.length; i++) {
            tappe[i].distance = (((route.legs[i].distance.text).split(" "))[0]).replace(",",".");
            tappe[i].duration = route.legs[i].duration.text;
            tappe[i].start_address = route.legs[i].start_address;
            tappe[i].end_address = route.legs[i].end_address;

            total_distance += parseFloat(tappe[i].distance);

            duration = route.legs[i].duration.text;
            if(duration.includes('ora')){
                duration = duration.split(" ora");
                duration[1] = duration[1].replace(" min", "");
            }else if (duration.includes('ore')) {
                duration = duration.split(" ore");
                duration[1] = duration[1].replace(" min", "");
            } else {
                duration = duration.split(" ");
                duration[1] = duration[0];
                duration[0] = "0";
            }

            total_hours += parseInt(duration[0]);
            total_min += parseInt(duration[1]);
        };

        //stringa durata
        total_hours += parseInt(total_min/60);
        var total_min_new = total_min - (parseInt(total_min/60) * 60);
        total_min_new = (total_min_new < 10) ? "0" + total_min_new : total_min_new;

        var string_ore = (total_hours <= 1)? " ora " : " ore ";
        var total_duration_string = total_hours + string_ore + total_min_new + " min";
        var total_duration_time = total_hours + parseInt(total_min/60) + ":" + total_min_new + ":00";

        //inserimento dati in tabella
        var $table = $('.tbl-percorso')
        var $body = $table.find('tbody');
        var $template = $table.find('tfoot');
        $body.html("");

        var letters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K","L", "M", "N",
            "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
        for (var i=0; i<2; i++) {
            letters = $.merge(letters, letters);
        }

        var ore = date.getHours();
        var min = date.getMinutes();
        var tempo;
        var item;

        for (var i=0; i<tappe.length; i++) {
            item = tappe[i];

            tempo = item.duration.split(" ");
            if (tempo.length == 2) { //solo min
                min = parseInt(min) + parseInt(tempo[0]);
                ore = parseInt(ore) + parseInt(min/60);
                min = parseInt(min) - (parseInt(min/60)*60);
            } else { //ore e min
                ore = parseInt(ore) + parseInt(tempo[0]);
                min = parseInt(min) + parseInt(tempo[2]);
                ore = parseInt(ore) + parseInt(min/60);
                min = parseInt(min) - (parseInt(min/60)*60);
            }

            $template.find('.numero-documento').text(item.numero);
            $template.find('.ord').text(letters[i+1]);
            $template.find('.ragione-sociale').text(item.ragione_sociale);
            $template.find('.indirizzo').text(item.indirizzo);
            $template.find('.citta').text(item.citta);
            $template.find('.cap').text(item.cap);
            $template.find('.provincia').text(item.provincia);
            $template.find('.tempo').text(item.duration);
            $template.find('.arrivo').text(ore + ":" + min);
            $template.find('.km').text(item.distance);
            $template.find('.colli').text(item.colli);
            $template.find('.azioni').html('<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>');

            $inputs = $template.find('.inputs');
            $inputs.find('[name="id[]"]').val(item.id);
            $inputs.find('[name="numero_documento[]"]').val(item.numero);
            $inputs.find('[name="tipo_consegna[]"]').val(item.tipo_consegna);
            $inputs.find('[name="ragione_sociale[]"]').val(item.ragione_sociale);
            $inputs.find('[name="indirizzo[]"]').val(item.indirizzo);
            $inputs.find('[name="citta[]"]').val(item.citta);
            $inputs.find('[name="cap[]"]').val(item.cap);
            $inputs.find('[name="provincia[]"]').val(item.provincia);
            $inputs.find('[name="tempo[]"]').val(item.duration);
            $inputs.find('[name="arrivo[]"]').val(ore + ":" + min);
            $inputs.find('[name="km[]"]').val(item.distance);
            $inputs.find('[name="colli[]"]').val(item.colli);
            $inputs.find('[name="ord[]"]').val(letters[i+1]);

            $body.append($template.html());
        }

        $('.tempo-totale').text(total_duration_time);
        $('.km-totali').text(total_distance.toFixed(1));
        $('[name="tempo_totale"]').val(total_duration_time);
        $('[name="km_totali"]').val(total_distance.toFixed(1));
    }

    /**
     * UTILITY
     */
    function loadTappe() {
        var $table = $(".dataTables_scrollBody");
        var $rows = $table.find("tbody");
        var tappe = [];
        var tmp;

        $rows.find(".selected").each(function() {
            tmp = {
                    id: $(this).find('span').attr('data-id'),
                    tipo_consegna: $(this).find("td:nth-child(2)").text(),
                    numero: $(this).find("td:nth-child(3)").text(),
                    ragione_sociale: $(this).find("td:nth-child(4)").text(),
                    indirizzo: $(this).find("td:nth-child(6)").text(),
                    citta: $(this).find("td:nth-child(7)").text(),
                    cap: $(this).find("td:nth-child(8)").text(),
                    provincia: $(this).find("td:nth-child(9)").text(),
                    colli: $(this).find("td:nth-child(10)").text(),
                }

            waypts.push({
                location: tmp.indirizzo + ", " + tmp.citta,
                stopover: true,
            });

            tappe.push(tmp);
        });

        var ret = {
            'waypts': waypts,
            'tappe': tappe
        };

        return ret;
    }

    // se locked = true allora prendo solo tappe bloccate
    // altrimenti (locked = false allora prendo tutto ciò che non è locked)
    function loadCurrentTappe(locked) {
        var $table = $(".tbl-percorso");
        var $rows = $table.find("tbody tr");
        var tappe = [];
        var waypts = [];

        var myClass = (locked) ? 'text-warning' : 'text-muted';

        var tmp;
        var lastLocked = {};
        var isPartial = false;
        var flag = false;

        $rows.each(function() {
            if (($(this).find('.padlock').hasClass(myClass))) {
                if (flag) {
                    waypts.push({
                        location: lastLocked.indirizzo + ", " + lastLocked.citta,
                        stopover: true,
                    });

                    tappe.push(lastLocked);

                    flag = false;
                }

                tmp = {
                    id: $(this).find('[name="id[]"]').val(),
                    numero: $(this).find('.numero-documento').text(),
                    tipo_consegna: $(this).find('[name="tipo_consegna[]"]').val(),
                    ragione_sociale: $(this).find('.ragione-sociale').text(),
                    indirizzo: $(this).find('.indirizzo').text(),
                    citta: $(this).find('.citta').text(),
                    cap: $(this).find('.cap').text(),
                    provincia: $(this).find('.provincia').text(),
                    colli: $(this).find('.colli').text(),
                }

                waypts.push({
                    location: tmp.indirizzo + ", " + tmp.citta,
                    stopover: true,
                });

                tappe.push(tmp);
            } else {
                isPartial = true;
                flag = true;

                lastLocked = {
                    id: $(this).find('.id').text(),
                    numero: $(this).find('.numero-documento').text(),
                    tipo_consegna: $(this).find('[name="tipo_consegna[]"]').val(),
                    ragione_sociale: $(this).find('.ragione-sociale').text(),
                    indirizzo: $(this).find('.indirizzo').text(),
                    citta: $(this).find('.citta').text(),
                    cap: $(this).find('.cap').text(),
                    provincia: $(this).find('.provincia').text(),
                    colli: $(this).find('.colli').text(),
                }
            }
        });

        var ret = {
            'waypts': waypts,
            'tappe': tappe,
            'isPartial': isPartial
        };

        return ret;
    }

    function moveDown($this) {
        var $row = $this.closest('tr');
        $row.next().after($row);
        disableRiordinaGiro(false);
        disableRicalcolaGiro(false);
    }

    function moveUp($this) {
        var $row = $this.closest('tr');
        $row.prev().before($row);
        disableRiordinaGiro(false);
        disableRicalcolaGiro(false);
    }

    function disableRiordinaGiro(flag) {
        if (flag) {
            $('.btn-riordina-giro').attr("disabled", "disabled",);
        } else {
            $('.btn-riordina-giro').removeAttr("disabled");
        }
    }

    function disableRicalcolaGiro(flag) {
        if (flag) {
            $('.btn-ricalcola-giro').attr("disabled", "disabled",);
        } else {
            $('.btn-ricalcola-giro').removeAttr("disabled");
        }
    }

    function disableAggiungiTappa(flag) {
        if (flag) {
            $('.btn-aggiungi-tappa').attr("disabled", "disabled",);
        } else {
            $('.btn-aggiungi-tappa').removeAttr("disabled");
        }
    }
</script>
