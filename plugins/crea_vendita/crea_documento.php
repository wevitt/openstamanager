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

use Modules\DDT\DDT;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\VenditaBanco\Vendita;

// Informazioni utili
$dir = $documento->direzione;
$original_module = Modules::get($id_module);

$name = !empty($documento_finale) ? $documento_finale->module : $options['module'];
$final_module = Modules::get($name);

if ($original_module->name == 'Interventi') {
    $documento = Intervento::find($id_record);
} elseif ($original_module->name == 'Preventivi') {
    $documento = Preventivo::find($id_record);
} elseif ($original_module->name == 'DDT in uscita') {
    $documento = DDT::find($id_record);
} elseif ($original_module->name == 'Ordini cliente') {
    $documento = Ordine::find($id_record);
}
$module = Modules::get($documento->module);

$final_module = 'Vendita al banco';
$op = 'crea_vendita';
$tipo_documento_finale = Vendita::class;

$options = [
    'op' => $op,
    'type' => 'ordine',
    'module' => $final_module,
    'button' => tr('Aggiungi'),
    'create_document' => true,
    'serials' => true,
    'documento' => $documento,
    'tipo_documento_finale' => $tipo_documento_finale,
];

// Inizializzazione
$documento = $options['documento'];
$documento_finale = $options['documento_finale'];
if (empty($documento)) {
    return;
}

// IVA predefinita
$id_iva = $id_iva ?: setting('Iva predefinita');

$righe = $documento->getRighe()->where('qta_rimanente', '>', 0);
if (empty($righe)) {
    echo '
<p>'.tr('Non ci sono elementi da evadere').'...</p>';

    return;
}

echo '
<form action="" method="post" role="form" id="crea_vendita">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="crea_vendita">';

// Creazione fattura dal documento
if (!empty($options['create_document'])) {
    echo '
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Nuovo documento').'</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <input type="hidden" name="create_document" value="on" />

                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
                </div>

            </div>
        </div>
    </div>';
}

    echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Opzioni generali delle righe').'</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle vendite ancora aperte?').'</small>", "placeholder": "'.tr('Aggiungere alle vendite ancora aperte?').'", "name": "accodare" ]}
                </div>
            </div>';
        if ($original_module->name == 'Interventi') {
            echo '
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Riporta manodopera').'", "name": "ore", "value": "1", "help":"'.tr('Aggiungi le sessioni di lavoro al documento di vendita').'" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Riporta diritto di chiamata').'", "name": "diritto", "value": "1", "help":"'.tr('Aggiungi il diritto di chiamata al documento di vendita').'" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Riporta trasferte').'", "name": "km", "value": "1", "help":"'.tr('Aggiungi le trasferte al documento di vendita').'" ]}
                </div>
            </div>';
        }
    echo '
        </div>
    </div>';

// Righe del documento
echo '
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Righe da importare').'</h3>
        </div>

        <table class="box-body table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th width="10%">'.tr('Q.tà').'</th>
                    <th width="15%">'.tr('Q.tà da evadere').'</th>
                    <th width="20%">'.tr('Subtot.').'</th>';

if (!empty($options['serials'])) {
    echo '
                    <th width="20%">'.tr('Seriali').'</th>';
}

echo '
                </tr>
            </thead>
            <tbody id="righe_documento_importato">';

foreach ($righe as $i => $riga) {
    // Descrizione
    echo '
                <tr data-local_id="'.$i.'">
                    <td>
                        <span class="hidden" id="id_articolo_'.$i.'">'.$riga['idarticolo'].'</span>

                        <input type="hidden" id="prezzo_unitario_'.$i.'" name="subtot['.$riga['id'].']" value="'.$riga['prezzo_unitario'].'" />
                        <input type="hidden" id="sconto_unitario_'.$i.'" name="sconto['.$riga['id'].']" value="'.$riga['sconto_unitario'].'" />
                        <input type="hidden" id="iva_unitaria_'.$i.'" name="iva['.$riga['id'].']" value="'.$riga['iva_unitaria'].'" />
                        <input type="hidden" id="max_qta_'.$i.'" value="'.($riga['qta_rimanente']).'" />';

    // Checkbox - da evadere?
    echo '
                        <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere['.$riga['id'].']" value="on" onclick="ricalcolaTotaleRiga('.$i.');" />';

    $descrizione = ($riga->isArticolo() ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

    echo '&nbsp;'.nl2br($descrizione);

    echo '
                    </td>';

    // Q.tà rimanente
    echo '
                    <td class="text-center">
                        '.numberFormat($riga['qta_rimanente']).'
                    </td>';

    // Q.tà da evadere
    echo '
                    <td>
                        {[ "type": "number", "name": "qta_da_evadere['.$riga['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$riga['qta_rimanente'].'", "decimals": "qta", "min-value": "0", "extra": "'.(($riga['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcolaTotaleRiga('.$i.');\"" ]}
                    </td>';

    echo '
                    <td>
                        <big id="subtotale_'.$i.'">'.moneyFormat($riga->totale).'</big><br/>

                        <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.numberFormat($riga->totale_imponibile).' + '.numberFormat($riga->iva).'</small>
                    </td>';

    // Seriali
    if (!empty($options['serials'])) {
        echo '
                    <td>';

        if (!empty($riga['abilita_serial'])) {
            $serials = $riga->serials;

            $list = [];
            foreach ($serials as $serial) {
                $list[] = [
                    'id' => $serial,
                    'text' => $serial,
                ];
            }

            if (!empty($serials)) {
                echo '
                        {[ "type": "select", "name": "serial['.$riga['id'].'][]", "id": "serial_'.$i.'", "multiple": 1, "values": '.json_encode($list).', "value": "'.implode(',', $serials).'", "extra": "data-maximum=\"'.intval($riga['qta_rimanente']).'\"" ]}';
            }
        }

        if (empty($riga['abilita_serial']) || empty($serials)) {
            echo '-';
        }

        echo '
                    </td>';
    }

    echo '
             </tr>';
}

// Totale
echo '
            </tbody>

            <tr>
                <td colspan="'.(!empty($options['serials']) ? 4 : 3).'" class="text-right">
                    <b>'.tr('Totale').':</b>
                </td>
                <td class="text-right" colspan="2">
                    <big id="totale"></big>
                </td>
            </tr>
        </table>
    </div>';

echo '
<div class="alert alert-warning hidden" id="articoli_sottoscorta">
    <table class="table table-condensed">
        <thead>
            <tr>
                <th>'.tr('Articolo').'</th>
                <th class="text-center tip" width="150" title="'.tr('Quantità richiesta').'">'.tr('Q.tà').'</th>
                <th class="text-center tip" width="150" title="'.tr('Quantità disponibile nel magazzino del gestionale').'">'.tr('Q.tà magazzino').'</th>
                <th class="text-center" width="150">'.tr('Scarto').'</th>
            </tr>
        </thead>

        <tbody></tbody>
    </table>
</div>';

echo '

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" id="submit_btn" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.$options['button'].'
            </button>
        </div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script type="text/javascript">
';

$articoli = $documento->articoli->groupBy('idarticolo');
$scorte = [];
foreach ($articoli as $elenco) {
    $qta = $elenco->sum('qta');
    $articolo = $elenco->first()->articolo;

    $descrizione_riga = $articolo->codice.' - '.$articolo->descrizione;
    $text = $articolo ? Modules::link('Articoli', $articolo->id, $descrizione_riga) : $descrizione_riga;

    $scorte[$articolo->id] = [
        'qta' => $articolo->qta,
        'descrizione' => $text,
        'servizio' => $articolo->servizio,
    ];
}

echo '
var scorte = '.json_encode($scorte).';
var abilita_scorte = '.intval(!$documento::$movimenta_magazzino && !empty($options['tipo_documento_finale']) && $options['tipo_documento_finale']::$movimenta_magazzino).';

function controllaMagazzino() {
    if(!abilita_scorte) return;

    let righe = $("#righe_documento_importato tr");

    // Lettura delle righe selezionate per l\'improtazione
    let richieste = {};
    for(const r of righe) {
        let riga = $(r);
        let id = $(riga).data("local_id");
        let id_articolo = riga.find("[id^=id_articolo_]").text();

        if (!$("#checked_" + id).is(":checked") || !id_articolo) {
            continue;
        }

        let qta = parseFloat(riga.find("input[id^=qta_]").val());
        richieste[id_articolo] = richieste[id_articolo] ? richieste[id_articolo] + qta : qta;
    }

    let sottoscorta = $("#articoli_sottoscorta");
    let body = sottoscorta.find("tbody");
    body.html("");

    for(const id_articolo in richieste) {
        let qta_scorta = parseFloat(scorte[id_articolo]["qta"]);
        let qta_richiesta = parseFloat(richieste[id_articolo]);
        if ((qta_richiesta > qta_scorta) && (scorte[id_articolo]["servizio"] !== 1) ) {
            body.append(`<tr>
        <td>` + scorte[id_articolo]["descrizione"] + `</td>
        <td class="text-right">` + qta_richiesta.toLocale() + `</td>
        <td class="text-right">` + qta_scorta.toLocale() + `</td>
        <td class="text-right">` + (qta_richiesta - qta_scorta).toLocale() + `</td>
    </tr>`);
        }
    }

    if (body.html()) {
        sottoscorta.removeClass("hidden");
    } else {
        sottoscorta.addClass("hidden");
    }
}

function ricalcolaTotaleRiga(r) {
    let prezzo_unitario = $("#prezzo_unitario_" + r).val();
    let sconto = $("#sconto_unitario_" + r).val();
    let iva = $("#iva_unitaria_" + r).val();

    let max_qta_input = $("#max_qta_" + r);
    let qta_max = max_qta_input.val() ? max_qta_input.val() : 0;

    prezzo_unitario = parseFloat(prezzo_unitario);
    sconto = parseFloat(sconto);
    iva = parseFloat(iva);
    qta_max = parseFloat(qta_max);

    let prezzo_scontato = prezzo_unitario - sconto;

    let qta = $("#qta_" + r).val().toEnglish();

    // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
    if (qta > qta_max) {
        qta = qta_max;

        $("#qta_" + r).val(qta);
    }

    // Se tolgo la spunta della casella dell\'evasione devo azzerare i conteggi
    if (isNaN(qta) || !$("#checked_" + r).is(":checked")) {
        qta = 0;
    }

    let serial_select = $("#serial_" + r);
    serial_select.selectClear();
    serial_select.select2("destroy");
    serial_select.data("maximum", qta);
    start_superselect();

    let subtotale = (prezzo_scontato * qta + iva * qta).toLocale();

    $("#subtotale_" + r).html(subtotale + " " + globals.currency);
    $("#subtotaledettagli_" + r).html((prezzo_scontato * qta).toLocale() + " + " + (iva * qta).toLocale());

    ricalcolaTotale();
}

function ricalcolaTotale() {
    let totale = 0.00;
    let totale_qta = 0;

    $("input[id*=qta_]").each(function() {
        let qta = $(this).val().toEnglish();
        let r = $(this).attr("id").replace("qta_", "");

        if (!$("#checked_" + r).is(":checked") || isNaN(qta)) {
            qta = 0;
        }

        let prezzo_unitario = $("#prezzo_unitario_" + r).val();
        let sconto = $("#sconto_unitario_" + r).val();
        let iva = $("#iva_unitaria_" + r).val();

        prezzo_unitario = parseFloat(prezzo_unitario);
        sconto = parseFloat(sconto);
        iva = parseFloat(iva);

        let prezzo_scontato = prezzo_unitario - sconto;

        if(prezzo_scontato) {
            totale += prezzo_scontato * qta + iva * qta;
        }

        totale_qta += qta;
    });

    $("#totale").html((totale.toLocale()) + " " + globals.currency);';

echo '
    controllaMagazzino();
}

ricalcolaTotale();
</script>';
