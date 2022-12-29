<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

use Modules\Interventi\Intervento;

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="8%" class="text-center">'.tr('Q.tà').'</th>
        <th width="10%" class="text-center">'.tr('Prezzo unitario').'</th>
        <th width="10%" class="text-center">'.tr('Sconto unitario').'</th>
        <th width="10%" class="text-center">'.tr('Iva unitaria').'</th>
        <th width="10%" class="text-center">'.tr('Reparto').'</th>
		<th width="10%" class="text-center">'.tr('Importo').'</th>
        <th width="8%" class="text-center">#</th>
    </tr>';

// Righe documento
$righe = $documento->getRighe();
foreach ($righe as $riga) {
    echo '
    <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'">';

    // Descrizione
    echo '
            <td>';

    if ($riga->isArticolo() && $riga->articolo->immagine) {
        echo '
            <img src="'.$riga->articolo->image.'" class="img img-thumbnail pull-right" style="max-height: 80px; max-width:120px">';
    }

    $descrizione = nl2br($riga->descrizione);
    if ($riga->isArticolo()) {
        $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->articolo->codice.' - '.$descrizione);
    }
    echo '
                '.$descrizione.'
                '.(!empty($riga->articolo->barcode) ? '<br><small>'.$riga->articolo->barcode.'</small>' : '');

    // Informazioni aggiuntive sulla destra
    echo '
                <small class="pull-right text-right text-muted">
                    '.$extra_riga;

    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                        <br>'.reference($riga->getOriginalComponent()->getDocument(), tr('Origine'));
    }
    // Fix per righe da altre componenti degli Interventi
    elseif (!empty($riga->idintervento)) {
        echo '
                        <br>'.reference(Intervento::find($riga->idintervento), tr('Origine'));
    }

    echo '
                    </small>

            </td>';

    if ($riga->isDescrizione()) {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
        <td class="text-center">
            '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
        </td>';

        // Prezzi unitari
        echo '
        <td class="text-right">
            '.(empty($riga->prezzo_unitario_corrente) ? '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' : '').'
            '.moneyFormat($riga->prezzo_unitario_corrente);

        if (abs($riga->sconto_unitario) > 0) {
            $text = discountInfo($riga);

            echo '
            <br><small class="label label-danger">'.$text.'</small>';
        }

        echo '
        </td>';

        if ($riga->isArticolo() || $riga->isRiga()) {
            echo '
        <td class="sconto-variabile">
            {[ "type": "number", "name": "sconto_'.$riga->id.'", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'", "help": "'.tr('Lo sconto viene applicato sull\'imponibile. Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
        </td>';
        } else {
            echo '<td class="text-center">-</td>';
        }

        // Iva
        echo '
        <td class="text-right">
            '.moneyFormat($riga->iva_unitaria).'
            <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
        </td>';

        echo '
        <td class="reparto-variabile">
            {[ "type": "select", "name": "id_reparto_'.$riga->id.'", "value": "'.($riga->id_reparto).'", "values": "query=SELECT id, CONCAT(codice, \' - \', descrizione) AS descrizione FROM vb_reparti" ]}
        </td>';

        // Importo
        echo '
        <td class="text-right">
            '.moneyFormat($riga->importo).'
        </td>';
    }

    // Possibilità di modificare le riga solo se la vendita non è pagata!
    echo '
            <td class="text-center">';

    if (!$documento->isPagato()) {
        echo '
                <div class="btn-group">
                    <button class="btn btn-xs btn-info" title="'.tr('Decrementa quantità').'" onclick="decrementaRiga(this)">
                        <i class="fa fa-minus"></i>
                    </button>

                    <button class="btn btn-xs btn-success" title="'.tr('Incrementa quantità').'" onclick="incrementaRiga(this)">
                        <i class="fa fa-plus"></i>
                    </button>

                    <button class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </button>

                    <button class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>';
    }

    echo '
            </td>
        </tr>';
}

echo '
    </tbody>';

// Calcoli
$imponibile = abs($documento->imponibile);
$sconto = $documento->sconto;
$totale_imponibile = abs($documento->totale_imponibile);
$iva = abs($documento->iva);
$totale = abs($documento->totale);

// Totale imponibile scontato
echo '
    <tr>
        <td colspan="6" class="text-right">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($documento->imponibile, 2).'
        </td>
        <td></td>
    </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
    <tr>
        <td colspan="6" class="text-right">
            <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
        </td>
        <td class="text-right">
            '.moneyFormat($documento->sconto, 2).'
        </td>
        <td></td>
    </tr>';

    // Totale imponibile scontato
    echo '
    <tr>
        <td colspan="6" class="text-right">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($totale_imponibile, 2).'
        </td>
        <td></td>
    </tr>';
}

// Totale iva
echo '
    <tr>
        <td colspan="6" class="text-right">
            <b>'.tr('Iva', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($documento->iva, 2).'
        </td>
        <td></td>
    </tr>';

// Totale
echo '
    <tr>
        <td colspan="6" class="text-right">
            <b>'.tr('Totale', [], ['upper' => true]).':</b>
        </td>
        <td class="text-right">
            '.moneyFormat($documento->totale, 2).'
            <span class="hidden" id="totale_documento">'.$documento->totale.'</span>
        </td>
        <td></td>
    </tr>';

// Margine
$margine = $documento->margine;
$margine_class = ($margine <= 0 and $documento->totale > 0) ? 'danger' : 'success';
$margine_icon = ($margine <= 0 and $documento->totale > 0) ? 'warning' : 'check';

echo '
    <tr>
        <td colspan="6"  class="text-right">
            '.tr('Costi').':
        </td>
        <td align="right">
            '.moneyFormat($documento->spesa).'
        </td>
        <td></td>
    </tr>

    <tr>
        <td colspan="6"  class="text-right">
            '.tr('Margine (_PRC_%)', [
        '_PRC_' => numberFormat($documento->margine_percentuale),
    ]).':
        </td>
        <td align="right" class="'.$margine_class.'">
            <i class="fa fa-'.$margine_icon.' text-'.$margine_class.'"></i> '.moneyFormat($documento->margine).'
        </td>
        <td></td>
    </tr>';

echo '
</table>';

echo '
<script>
$(document).ready(function() {
    $(".sconto-variabile input, .sconto-variabile select").on("change", function() {
        aggiornaSconto(this);
    });

    $(".reparto-variabile select").on("change", function() {
        aggiornaReparto(this);
    });

    // Trigger per aggiornamento resto
    $("#importo_pagato").change();
});

function aggiornaSconto(input) {
    content_was_modified = false;
    var riga = $(input).closest("tr");
    var id = riga.data("id");
    var type = riga.data("type");

    var sconto_input = riga.find("input");
    var tipo_sconto_input = riga.find("select");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "aggiorna-sconto",
            riga_type: type,
            riga_id: id,
            sconto: sconto_input.val(),
            tipo_sconto: tipo_sconto_input.val(),
        },
        success: function (response) {
            reloadRows();
        },
        error: function() {
            reloadRows();
        }
    });
}

function aggiornaReparto(input) {
    content_was_modified = false;
    const reparto_input = $(input);

    var riga = reparto_input.closest("tr");
    var id = riga.data("id");
    var type = riga.data("type");


    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "aggiorna-reparto",
            riga_type: type,
            riga_id: id,
            id_reparto: reparto_input.val(),
        },
        success: function (response) {
            //reloadRows();
        },
        error: function() {
            //reloadRows();
        }
    });
}

function modificaRiga(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");
    var type = riga.data("type");

    //e22d0d
    openModal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

function rimuoviRiga(button) {
    swal({
        title: "'.tr('Rimuovere questa riga?').'",
        html: "'.tr('Sei sicuro di volere rimuovere questa riga dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        var riga = $(button).closest("tr");
        var id = riga.data("id");
        var type = riga.data("type");

        $(button).prop("disabled", true);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_riga",
                riga_type: type,
                riga_id: id,
            },
            success: function (response) {
                reloadRows();
                $(button).prop("disabled", false);
            },
            error: function() {
                reloadRows();
                $(button).prop("disabled", false);
            }
        });
    }).catch(swal.noop);
}

function decrementaRiga(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");
    var type = riga.data("type");

    $(button).prop("disabled", true);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "decrementa_riga",
            riga_type: type,
            riga_id: id,
        },
        success: function (response) {
            reloadRows().then(function() {
                evidenziaRiga(id, "E08E0B");
                $(button).prop("disabled", false);
            });
        },
        error: function() {
            reloadRows();
            $(button).prop("disabled", false);
        }
    });
}

function incrementaRiga(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");
    var type = riga.data("type");

    $(button).prop("disabled", true);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "incrementa_riga",
            riga_type: type,
            riga_id: id,
        },
        success: function (response) {
            reloadRows().then(function() {
                evidenziaRiga(id, "008D4C");
                $(button).prop("disabled", false);
            });
        },
        error: function() {
            reloadRows();
            $(button).prop("disabled", false);
        }
    });
}

function evidenziaRiga(id, color) {
    $("tr[data-id=" + id + "]").effect("highlight", {color: "#" + color}, 2000);
}
</script>';
