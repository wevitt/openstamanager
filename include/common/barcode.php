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

$incorpora_iva = setting('Utilizza prezzi di vendita comprensivi di IVA');
$intestazione_prezzo = ($options['dir'] == 'uscita' ? tr('Prezzo di acquisto') : ($incorpora_iva ? tr('Prezzo vendita ivato') : tr('Prezzo vendita imponibile')));

// Articolo
echo '
<div class="row">
    <div class="col-md-6">
        {[ "type": "text", "label": "' . tr('Inserisci barcode manualmente') . '", "name": "barcode", "value": "", "icon-before": "<i class=\"fa fa-barcode\"></i>" ]}
    </div>
    <div class="col-md-6">
        {[ "type": "file", "label": "' . tr('Inserisci file') . '", "id": "barcode_file", "name": "barcode_file", "value": "", "icon-before": "<i class=\"fa fa-file\"></i>", "multiple": true ]}
    </div>
</div>

<div class="alert alert-info hidden" id="articolo-missing">
    <i class="fa fa-exclamation-circle"></i> '.tr('Nessuna corrispondenza trovata!').'
</div>

<div class="alert alert-warning hidden" id="articolo-qta">
    <i class="fa fa-warning"></i> '.tr('Articolo con quantità non sufficiente!').'
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-stripped hide" id="articoli_barcode">
            <tr>
                <th>'.tr('Articolo').'</th>
                <th width="25%" class="text-center">'.$intestazione_prezzo.'</th>
                <th width="20%" class="text-center">'.tr('Sconto').'</th>
                <th width="10%" class="text-center">'.tr('Q.tà').'</th>
                <th width="5%" class="text-center">#</th>
            </tr>
        </table>
    </div>
</div>

<input type="hidden" name="permetti_movimenti_sotto_zero" id="permetti_movimenti_sotto_zero" value="'.setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita').'">';

echo '
<script>
var direzione = "'.$options['dir'].'";

$(document).ready(function(){
    init();

    setTimeout(function(){
        $("#barcode").focus();
    }, 300);

    $(".modal-body button").attr("disabled", true);
});


// gestione drop file
$("#barcode_file").on("change", function (event) {
    let files = event.target.files;
    //read csv file
    let reader = new FileReader();
    reader.readAsText(files[0]);
    reader.onload = function (event) {
        var csv = event.target.result;
        var lines = csv.split("\n");
        console.log(lines);
        lines.forEach(function (line) {
            if (line === "") {
                return;
            }
            var barcode = line.split(";")[0];
            var qta = line.split(";")[1];

            barcodeAdd(barcode, qta);
        });

    };
});

// Gestione dell\'invio da tastiera
$(document).keypress(function(event){
    let key = window.event ? event.keyCode : event.which; // IE vs Netscape/Firefox/Opera
    if (key == "13") {
        event.preventDefault();
        $("#barcode").blur()
            .focus();
    }
});

$("#barcode").off("keyup").on("keyup", function (event) {
    let key = window.event ? event.keyCode : event.which; // IE vs Netscape/Firefox/Opera
    $("#articolo-missing").addClass("hidden");
    $("#articolo-qta").addClass("hidden");

    if (key !== 13) {
        return;
    }

    $("#barcode").attr("disabled", true);
    var barcode = $("#barcode").val();
    if (!barcode){
        barcodeReset();
        return;
    }

    barcodeAdd(barcode, 1);
});

function barcodeAdd(barcode, qta) {
    $.getJSON(globals.rootdir + "/ajax_select.php?op=articoli_barcode&search=" + barcode + "&id_anagrafica='.$options['idanagrafica'].'", function(response) {
        let result = response.results[0];
        if(!result){
            $("#articolo-missing").removeClass("hidden");
            barcodeReset();

            return;
        }

        let qta_input = $("#riga_barcode_" + result.id).find("[name^=qta]");
        let permetti_movimenti_sotto_zero = $("#permetti_movimenti_sotto_zero").val();
        if (result.qta <= 0 && permetti_movimenti_sotto_zero == 0 && direzione === "entrata") {
            $("#articolo-qta").removeClass("hidden");
            barcodeReset();
            return;
        }

        // Controllo se è già presente l\'articolo, in tal caso incremento la quantità, altrimenti inserisco la riga nuova
        if (qta_input.length) {
            let qta = qta_input.val().toEnglish();
            let nuova_qta = qta + 1;

            if (result.qta < nuova_qta) {
                $("#articolo-qta").removeClass("hidden");
                barcodeReset();
                return;
            }

            qta_input.val(nuova_qta).trigger("change");
        } else {
            let prezzo_unitario = (direzione === "uscita") ? result.prezzo_acquisto : result.prezzo_vendita;
            prezzo_acquisto = parseFloat(result.prezzo_acquisto, 10).toLocale();
            prezzo_vendita = parseFloat(result.prezzo_vendita, 10).toLocale();

            let info_prezzi;
            if(direzione === "entrata") {
                info_prezzi = "Acquisto: " + (prezzo_acquisto) + " &euro;";
            }else{
                info_prezzi = "Vendita: " + (prezzo_vendita) + " &euro;";
            }

            $("#articoli_barcode").removeClass("hide");
            cleanup_inputs();

            var text = replaceAll($("#barcode-template").html(), "-id-", result.id);
            text = text.replace("|prezzo_unitario|", prezzo_unitario)
                .replace("|info_prezzi|", info_prezzi)
                .replace("|descrizione|", result.descrizione)
                .replace("|codice|", result.codice)
                .replace("|qta|", qta)
                .replace("|sconto_unitario|", 0)
                .replace("|tipo_sconto|", "")
                .replace("|id_dettaglio_fornitore|", result.id_dettaglio_fornitore ? result.id_dettaglio_fornitore : "")

            $("#articoli_barcode tr:last").after(text);
            restart_inputs();

            $(".modal-body button").attr("disabled", false);

            // Gestione dinamica dei prezzi
            let tr = $("#riga_barcode_" + result.id);
            ottieniDettagliArticolo(result.id, tr).then(function() {
                if ($(tr).find("input[name^=prezzo_unitario]").val().toEnglish() === 0){
                    aggiornaPrezzoArticolo(tr);
                } else {
                    verificaPrezzoArticolo(tr);
                }

                if ($(tr).find("input[name^=sconto]").val().toEnglish() === 0){
                    aggiornaScontoArticolo();
                } else {
                    verificaScontoArticolo();
                }
            });
        }

        barcodeReset();
        $("#barcode").val("");
    }, function(){
        $("#articolo-missing").removeClass("hidden");
        barcodeReset();
    });
}

$(document).on("change", "input[name^=qta], input[name^=prezzo_unitario]", function() {
    let tr = $(this).closest("tr");
    verificaPrezzoArticolo(tr);
});

function barcodeReset() {
    setTimeout(function(){
        $("#barcode")
            .attr("disabled",false)
            .focus();
    },200);
}

function rimuoviRigaBarcode(id) {
    if (confirm("'.tr('Eliminare questo articolo?').'")) {
        $("#riga_barcode_" + id).remove();

        // Disabilito il pulsante di aggiunta se non ci sono articoli inseriti
        if ($(".inputmask-decimal").length === 0) {
            $(".modal-body button").attr("disabled", true);
            $("#articoli_barcode").addClass("hide");
        }
    }
}

/**
* Restituisce il prezzo registrato per una specifica quantità dell\'articolo.
*/
function getDettaglioPerQuantita(qta, tr) {
    const data = $(tr).data("dettagli");
    if (!data) return null;

    let dettaglio_predefinito = null;
    let dettaglio_selezionato = null;
    for (const dettaglio of data) {
        if (dettaglio.minimo == null && dettaglio.massimo == null) {
            dettaglio_predefinito = dettaglio;
            continue;
        }

        if (qta >= dettaglio.minimo && qta <= dettaglio.massimo) {
            dettaglio_selezionato = dettaglio;
        }
    }

    if (dettaglio_selezionato == null) {
        dettaglio_selezionato = dettaglio_predefinito;
    }

    return dettaglio_selezionato;
}

/**
* Restituisce il prezzo registrato per una specifica quantità dell\'articolo.
*/
function getPrezzoPerQuantita(qta, tr) {
    const dettaglio = getDettaglioPerQuantita(qta, tr);

    return dettaglio ? parseFloat(dettaglio.prezzo_unitario) : 0;
}

/**
* Restituisce lo sconto registrato per una specifica quantità dell\'articolo.
*/
function getScontoPerQuantita(qta, tr) {
    const dettaglio = getDettaglioPerQuantita(qta, tr);

    return dettaglio ? parseFloat(dettaglio.sconto_percentuale) : 0;
}

/**
* Funzione per registrare localmente i dettagli definiti per l\'articolo in relazione ad una specifica anagrafica.
*/
function ottieniDettagliArticolo(id_articolo, tr) {
    return $.get(globals.rootdir + "/ajax_complete.php?module=Articoli&op=dettagli_articolo&id_anagrafica='.$options['idanagrafica'].'&id_articolo=" + id_articolo + "&dir=" + direzione, function(response) {
        const data = JSON.parse(response);

        $(tr).data("dettagli", data);
    });
}

/**
* Funzione per verificare se il prezzo unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaPrezzoArticolo(tr) {
    let qta = $(tr).find("input[name^=qta]").val().toEnglish();
    let prezzo_previsto = getPrezzoPerQuantita(qta, tr);

    let prezzo_unitario_input = $(tr).find("input[name^=prezzo_unitario]");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();

    let div = prezzo_unitario_input.closest("div").parent().find("div[id*=errors]");

    if (prezzo_previsto === prezzo_unitario || prezzo_previsto === 0) {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "5px");
    div.html(`<small class="label label-info" >'.tr('Prezzo suggerito').': ` + prezzo_previsto.toLocale() + " " + globals.currency + `<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per aggiornare il prezzo unitario sulla base dei valori automatici.
*/
function aggiornaPrezzoArticolo(button) {
    let tr = $(button).closest("tr");
    let qta = tr.find("input[name^=qta]").val().toEnglish();
    let prezzo_previsto = getPrezzoPerQuantita(qta, tr);

    tr.find("input[name^=prezzo_unitario]").val(prezzo_previsto).trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        aggiorna_guadagno();
    }
}

/**
* Funzione per verificare se lo sconto unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaScontoArticolo(tr) {
    let qta = $(tr).find("input[name^=qta]").val().toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta, tr);

    let sconto_input = $(tr).find("input[name^=sconto]");
    let sconto = sconto_input.val().toEnglish();

    let div = sconto_input.parent().next();
    if (sconto_previsto === 0 || sconto_previsto === sconto || $(tr).find("input[name^=tipo_sconto]").val() === "UNT") {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "5px");
    div.html(`<small class="label label-info" >'.tr('Sconto suggerito').': ` + sconto_previsto.toLocale()  + `%<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaScontoArticolo(this)"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per aggiornare lo sconto unitario sulla base dei valori automatici.
*/
function aggiornaScontoArticolo(button) {
    let tr = $(button).closest("tr");
    let qta = tr.find("input[name^=qta]").val().toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta, tr);

    $("#sconto").val(sconto_previsto).trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        aggiorna_guadagno();
    }
}
</script>

<table class="hidden">
    <tbody id="barcode-template">
        <tr id="riga_barcode_-id-">
            <td>
                |codice| - |descrizione|
                <br><small>|info_prezzi|</small>
                <input type="hidden" name="id_dettaglio_fornitore[-id-]" value="|id_dettaglio_fornitore|">
            </td>
            <td>
                {[ "type": "number", "name": "prezzo_unitario[-id-]", "value": "|prezzo_unitario|", "required": 0, "icon-after": "'.currency().'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "sconto[-id-]", "value": "|sconto_unitario|", "icon-after": "choice|untprc||tipo_sconto|", "help": "'.tr('Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "qta[-id-]", "required": 0, "value": "|qta|", "decimals": "qta" ]}
            </td>

            <td width="5%" class="text-center">
                <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviRigaBarcode(\'-id-\')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>';
