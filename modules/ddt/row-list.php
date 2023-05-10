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

include_once __DIR__.'/init.php';

$block_edit = $record['flag_completato'];
$righe = $ddt->getRighe();
$colspan = ($block_edit ? '6' : '7');
$direzione = $ddt->direzione;

echo '
<div class="table-responsive row-list">
    <table class="table table-striped table-hover table-condensed table-bordered">
        <thead>
            <tr>
                <th width="5" class="text-center">';
                if (!$block_edit && sizeof($righe) > 0) {
                    echo '
                    <input id="check_all" type="checkbox"/>';
                }
                echo '
                </th>
                <th width="35" class="text-center">'.tr('#').'</th>
                <th>'.tr('Descrizione').'</th>
                <th class="text-center tip" width="150">'.tr('Q.tà').'</th>
                <th class="text-center" width="140">'.tr('Prezzo unitario').'</th>';
            if (!$block_edit) {
                echo '<th class="text-center" width="150">'.tr('Sconto unitario').'</th>';
            }
            echo '
                <th class="text-center" width="140">'.tr('Iva totale').'</th>
                <th class="text-center" width="140">'.tr('Importo totale').'</th>
                <th width="80"></th>
            </tr>
        </thead>

        <tbody class="sortable" id="righe">';

// Righe documento
$num = 0;
$riga_spesa_trasporto = null;
$riga_spesa_incasso = null;

$ive = [];
$imponibile = [];
$totale_iva = 0;
foreach ($righe as $riga) {
    $current_iva = floatval($database->fetchOne('SELECT * FROM dt_righe_ddt WHERE id = '.$riga->id)['iva']);
    $row_iva = $dbo->fetchOne('SELECT * FROM co_iva WHERE id = ?', [$riga->idiva]);
    $totale_iva += $current_iva;

    if (!isset($ive[$row_iva['percentuale']])) {
        $ive[$row_iva['percentuale']] = 0;
        $imponibile[$row_iva['percentuale']] = 0;
    }
    $ive[$row_iva['percentuale']] += $current_iva;
    $imponibile[$row_iva['percentuale']] += $riga->imponibile;

    if ($riga->is_spesa_trasporto == 1) {
        $riga_spesa_trasporto = $riga;
    } else if ($riga->is_spesa_incasso) {
        $riga_spesa_incasso = $riga;
    } else {
        ++$num;

        $extra = '';
        $mancanti = 0;

        // Individuazione dei seriali
        if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
            $serials = $riga->serials;
            $mancanti = abs($riga->qta) - count($serials);

            if ($mancanti > 0) {
                $extra = 'class="warning"';
            } else {
                $mancanti = 0;
            }
        }

        echo '
            <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'" '.$extra.'>
                <td class="text-center">';
                if (!$block_edit) {
                    echo '
                    <input class="check" type="checkbox"/>';
                }
                echo '
                </td>

                <td class="text-center">
                    '.$num.'
                </td>

                <td>
                    <small class="pull-right text-right text-muted">';

        $numero_riferimenti_riga = $riga->referenceTargets()->count();
        $numero_riferimenti_collegati = $riga->referenceSources()->count();
        $riferimenti_presenti = $numero_riferimenti_riga;
        $testo_aggiuntivo = $riferimenti_presenti ? $numero_riferimenti_riga : '';
        echo '
                        <button type="button" class="btn btn-xs btn-'.($riferimenti_presenti ? 'primary' : 'info').'" onclick="apriRiferimenti(this)">
                            <i class="fa fa-chevron-right"></i> '.tr('Riferimenti').' '.$testo_aggiuntivo.'
                        </button>';

        // Aggiunta dei riferimenti ai documenti
        if ($riga->hasOriginalComponent()) {
            echo '
                        <br>'.reference($riga->getOriginalComponent()->getDocument(), tr('Origine'));
        }
        echo '
                    </small>';

        if ($riga->isArticolo()) {
            echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
        } else {
            echo nl2br($riga->descrizione);
        }

        if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
            if (!empty($mancanti)) {
                echo '
                    <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                            '_NUM_' => $mancanti,
                        ]).'</small></b>';
            }
            if (!empty($serials)) {
                echo '
                    <br>'.tr('SN').': '.implode(', ', $serials);
            }
        }

        if ($riga->isArticolo() && !empty($riga->articolo->barcode)) {
            echo '
            <br><small><i class="fa fa-barcode"></i> '.$riga->articolo->barcode.'</small>';
        }


        if (!empty($riga->note)) {
                echo '
                    <br><small class="label label-default">'.nl2br($riga->note).'</small>';
        }
        echo '
                </td>';

    if ($riga->isDescrizione()) {
        echo '
                <td></td>
                <td></td>
                <td></td>
                <td></td>';
    } else {
                // Quantità e unità di misura
                $progress_perc = $riga->qta_evasa * 100 / $riga->qta;
                echo '
                <td class="text-center">
                    {[ "type": "number", "name": "qta_'.$riga->id.'", "value": "'.$riga->qta.'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "<span class=\'tip\' title=\''.tr('Quantità evasa: _QTA_', ['_QTA_' => numberFormat($riga->qta_evasa, 'qta')]).'\'>'.($riga->um ?: '&nbsp;').'</span>", "disabled": "'.($riga->isSconto() ? 1 : 0).'", "disabled": "'.$block_edit.'" ]}
                    <div class="progress" style="height:4px;">
                        <div class="progress-bar progress-bar-primary" style="width:'.$progress_perc.'%"></div>
                    </div>
                </td>';

        // Prezzi unitari
        echo '
                <td class="text-right">';
                    // Provvigione riga
                    if (abs($riga->provvigione_unitaria) > 0) {
                        $text = provvigioneInfo($riga);
                        echo '<span class="pull-left text-info" title="'.$text.'"><i class="fa fa-handshake-o"></i></span>';
                    }
                    echo moneyFormat($riga->prezzo_unitario_corrente);

        if ($dir == 'entrata' && $riga->costo_unitario != 0) {
            echo '
                    <br><small class="text-muted">
                        '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
                    </small>';
        }

        if (abs($riga->sconto_unitario) > 0) {
            $text = discountInfo($riga);

            echo '
                    <br><small class="label label-danger">'.$text.'</small>';
        }

        echo '
                </td>';

        // Sconto unitario
        if (!$block_edit) {
            echo '
                <td class="text-center">
                    {[ "type": "number", "name": "sconto_'.$riga->id.'", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'" ]}
                </td>';
        }

        // Iva totale
        echo '
        <td class="text-right">
            '.moneyFormat($current_iva);
            if (!$block_edit) {
                echo '
                <div class="btn-group">
                    <a class="btn btn-xs btn-default" onclick="incrementa_riduci($(this), 0.01, \'iva\')">
                        <i class="fa fa-arrow-up"></i>
                    </a>
                    <a class="btn btn-xs btn-default" onclick="incrementa_riduci($(this), -0.01, \'iva\')">
                        <i class="fa fa-arrow-down"></i>
                    </a>
                </div>';
            }
            echo '
            <span style="padding-right:50px">
                <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
            </span>
        </td>';

        // Importo
        echo '
        <td class="text-right">
            '.moneyFormat($riga->importo);
            if (!$block_edit) {
                echo '
                <div class="btn-group">
                    <a class="btn btn-xs btn-default" onclick="incrementa_riduci($(this), 0.01, \'importo\')">
                        <i class="fa fa-arrow-up"></i>
                    </a>
                    <a class="btn btn-xs btn-default" onclick="incrementa_riduci($(this), -0.01, \'importo\')">
                        <i class="fa fa-arrow-down"></i>
                    </a>
                </div>';
            }
            // Iva
            echo '
            <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
        </td>';
    }

        // Possibilità di rimuovere una riga solo se il ddt non è evaso
        echo '
                <td class="text-center">';

                if ($record['flag_completato'] == 0) {
                echo '
                    <div class="input-group-btn">';

                if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
                echo '
                        <a class="btn btn-primary btn-xs" title="'.tr('Modifica seriali della riga').'" onclick="modificaSeriali(this)">
                            <i class="fa fa-barcode"></i>
                        </a>';
                }

                echo '
                        <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                            <i class="fa fa-edit"></i>
                        </a>

                        <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga([$(this).closest(\'tr\').data(\'id\')])">
                            <i class="fa fa-trash"></i>
                        </a>

                        <a class="btn btn-xs btn-default handle" title="'.tr('Modifica ordine delle righe').'">
                            <i class="fa fa-sort"></i>
                        </a>
                    </div>';
                }

                echo '
                </td>
            </tr>';
    }
}

$tot = [];
foreach ($ive as $key => $iva) {
    if (!isset($tot[$key])) {
        $tot[$key] = 0;
    }
    $tot[$key] = [
        'imponibile' => $imponibile[$key],
        'iva' => $iva,
    ];
}

echo '<div class="json-tot hide">'.htmlspecialchars(json_encode($tot)).'</div>';

echo '
        </tbody>';
// Calcoli
$imponibile = abs($ddt->imponibile);
$sconto = -$ddt->sconto;
$totale_imponibile = abs($ddt->totale_imponibile);
//$iva = abs($ddt->iva);
$totale = abs($ddt->totale);
$sconto_finale = $ddt->getScontoFinale();
$netto_a_pagare = $ddt->netto;

// SPESA TRASPORTO
if (!empty($riga_spesa_trasporto)) {
    echo '<div class="spesa-trasporto hide">'.$riga_spesa_trasporto->subtotale.'</div>';

    echo '
    <tr data-id="'.$riga_spesa_trasporto->id.'" data-type="'.get_class($riga_spesa_trasporto).'">
        <td colspan="'.$colspan.'" class="text-right">
            <b>
                <span class="tip" title="'.tr('Spesa di trasporto').'">
                    '.tr('Spesa di trasporto', [], ['upper' => true]).':
                </span>
            </b>
        </td>

        <td class="text-right">
            '.moneyFormat($riga_spesa_trasporto->subtotale, 2).'
        </td>

        <td class="text-center">';
            if ($record['flag_completato'] == 0) {
                echo '
                <div class="input-group-btn">
                    <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </a>
                </div>';
            }
        echo '
        </td>
    </tr>';
}

// SPESA INCASSO
if (!empty($riga_spesa_incasso)) {
    echo '<div class="spesa-incasso hide">'.$riga_spesa_incasso->subtotale.'</div>';

    echo '
    <tr data-id="'.$riga_spesa_incasso->id.'" data-type="'.get_class($riga_spesa_incasso).'">
        <td colspan="'.$colspan.'" class="text-right">
            <b>
                <span class="tip" title="'.tr('Spesa di incasso').'">
                    '.tr('Spesa di incasso', [], ['upper' => true]).':
                </span>
            </b>
        </td>

        <td class="text-right riga-spesa-incasso">
            '.moneyFormat($riga_spesa_incasso->subtotale, 2).'
        </td>

        <td class="text-center">';
            if ($record['flag_completato'] == 0) {
                echo '
                <div class="input-group-btn">
                    <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </a>
                </div>';
            }
        echo '
        </td>
    </tr>';
}

// IMPONIBILE
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($imponibile, 2).'
            </td>

            <td></td>
        </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b><span class="tip" title="'.tr('Un importo negativo indica uno sconto, mentre uno positivo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>

            <td class="text-right">
                '.moneyFormat($sconto, 2).'
            </td>

            <td></td>
        </tr>';

    // TOTALE IMPONIBILE
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
            </td>

            <td class="text-right">
                '.moneyFormat($totale_imponibile, 2).'
            </td>

            <td></td>
        </tr>';
}


// IVA
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Iva', [], ['upper' => true]).'</b>
                <small>
                    <span class="tooltip-iva" title="">
                        <i class="fa fa-question-circle-o"></i>
                    </span>
                </small>
                :
            </td>
            <td class="text-right">
                '.moneyFormat(abs($totale_iva), 2).'
            </td>

            <td></td>
        </tr>';

// TOTALE
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale documento', [], ['upper' => true]).'</b>
                <small>
                    <span class="tooltip-totale" title="">
                        <i class="fa fa-question-circle-o"></i>
                    </span>
                </small>
                :
            </td>
            <td class="text-right">
                '.moneyFormat($totale, 2).'
            </td>

            <td></td>
        </tr>';

// SCONTO IN FATTURA
if (!empty($sconto_finale)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Sconto in fattura', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($sconto_finale, 2).'
            </td>
            <td></td>
        </tr>';
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($netto_a_pagare, 2).'
            </td>
            <td></td>
        </tr>';
}

$margine = $ddt->margine;
$margine_class = ($margine <= 0 && $ddt->totale > 0) ? 'danger' : 'success';
$margine_icon = ($margine <= 0 && $ddt->totale > 0) ? 'warning' : 'check';

echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right">
            '.tr('Costi').':
        </td>
        <td class="text-right">
            '.moneyFormat($ddt->spesa).'
        </td>
        <td></td>
    </tr>';


// Provvigione
if(!empty($ddt->provvigione)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
            '.tr('Provvigioni', [], ['upper' => false]).':
            </td>
            <td class="text-right">
                '.moneyFormat($ddt->provvigione).'
            </td>
            <td></td>
        </tr>';
}

    echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right">
            '.tr('Margine (_PRC_%)', [
                '_PRC_' => numberFormat($ddt->margine_percentuale),
        ]).':
        </td>
        <td class="text-right '.$margine_class.'" rowspan="2" style="vertical-align:middle;">
            <i class="fa fa-'.$margine_icon.' text-'.$margine_class.'"></i>
            <span class="riga-margine">'.moneyFormat($ddt->margine).'</span>
        </td>
        <td rowspan="2"></td>
    </tr>

    <tr>
        <td colspan="'.$colspan.'" class="text-right">
            '.tr('Ricarico (_PRC_%)', [
                '_PRC_' => numberFormat($ddt->ricarico_percentuale),
        ]).':
        </td>
    </tr>';

echo '
    </table>';
if (!$block_edit && sizeof($righe) > 0) {
    echo '
    <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default disabled" id="duplica_righe" onclick="duplicaRiga(getSelectData());">
            <i class="fa fa-copy"></i>
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="elimina_righe" onclick="rimuoviRiga(getSelectData());">
            <i class="fa fa-trash"></i>
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="subtotale_righe" onclick="calcolaSubtotale(\'ddt\');">
            <i class="fa fa-calculator"></i> Subtotale
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="confronta_righe" onclick="confrontaRighe(getSelectData());">
            Confronta prezzi
        </button>
    </div>';
}
echo '
</div>

<script>
async function modificaRiga(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Chiusura tooltip
    if ($(button).hasClass("tooltipstered"))
        $(button).tooltipster("close");

    // Apertura modal
    content_was_modified = false;
    openModal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

// Estraggo le righe spuntate
function getSelectData() {
    let data=new Array();
    $(\'#righe\').find(\'.check:checked\').each(function (){
        data.push($(this).closest(\'tr\').data(\'id\'));
    });

    return data;
}

function confrontaRighe(id) {
    openModal("'.tr('Confronta prezzi').'", "'.$module->fileurl('modals/confronta_righe.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&righe=" + id + "&id_anagrafica='.$ordine->idanagrafica.'&direzione='.$dir.'");
}

function rimuoviRiga(id) {
    swal({
        title: "'.tr('Rimuovere queste righe?').'",
        html: "'.tr('Sei sicuro di volere rimuovere queste righe dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_riga",
                righe: id,
            },
            success: function (response) {
                content_was_modified = false;
                location.reload();
            },
            error: function() {
                location.reload();
            }
        });
    }).catch(swal.noop);
}

function duplicaRiga(id) {
    swal({
        title: "'.tr('Duplicare queste righe?').'",
        html: "'.tr('Sei sicuro di volere queste righe del documento?').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "copy_riga",
                righe: id,
            },
            success: function (response) {
                location.reload();
            },
            error: function() {
                location.reload();
            }
        });
    }).catch(swal.noop);
}

function modificaSeriali(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Aggiorna SN').'", globals.rootdir + "/modules/fatture/add_serial.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

function apriRiferimenti(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Riferimenti riga').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=visualizza_righe_riferimenti&riga_id=" + id + "&riga_type=" + type)
}

$(document).ready(function() {
	sortable(".sortable", {
        axis: "y",
        handle: ".handle",
        cursor: "move",
        dropOnEmpty: true,
        scroll: true,
    })[0].addEventListener("sortupdate", function(e) {
        let order = $(".table tr[data-id]").toArray().map(a => $(a).data("id"))

        $.post(globals.rootdir + "/actions.php", {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "update_position",
            order: order.join(","),
        });
    });
    tooltipIva();
    tooltipImponibile();

    changeSpesaIncasso();
});

function changeSpesaIncasso() {
    //on change by name=idpagamento
    $("select[name=idpagamento]").on("change", function() {
        let id_pagamento = $(this).val();
        let id_anagrafica = "'.$fattura->idanagrafica.'";

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "get_spesa_incasso",
                idpagamento: id_pagamento,
                idanagrafica: id_anagrafica,
            },
            success: function (response) {
                caricaRighe();
                renderMessages();
            },
            error: function() {
                caricaRighe(null);
            }
		});
    });

    function castFloat(number) {
        number = number.replace(".", "");
        number = number.replace(",", ".");
        return parseFloat(number);
    }

    function recastFloat(number) {
        number = number.toString();
        number = number.replace(".", ",");
        number = number.split(",");
        if (number[1] != undefined) {
            number[1] = number[1].substring(0, 2);
            if (number[1].length == 1) {
                number[1] += "0";
            }
        } else {
            number[1] = "00";
        }
        number = number.join(",");

        return number;
    }
}

function tooltipIva() {
    var tot = JSON.parse($(".json-tot").html());

    var content = "";
    $.each(tot, function(key, value) {
        content +=
            "iva " + key + "%: " + value["iva"].toFixed(2) + "€<br>";
    });

    var $icon = $(".tooltip-iva");

    $icon.tooltipster({
        content: content,
        contentAsHTML: true,
        trigger: "click",
        interactive: true,
        touchDevices: true,
    });
}

function tooltipImponibile() {
    var tot = JSON.parse($(".json-tot").html());
    var speseTrasporto = ($(".spesa-trasporto").length) ? parseFloat($(".spesa-trasporto").html()) : -1;
    var speseIncasso = ($(".spesa-incasso").length) ? parseFloat($(".spesa-incasso").html()) : -1;


    var content = "";
    $.each(tot, function(key, value) {
        content +=
            value["imponibile"].toFixed(2) + "€ imponibile + " + value["iva"].toFixed(2) + "€ iva al " + key + "%<br>";
    });

    if (speseTrasporto != -1) {
        content += "<br>";
        content += "Spese di trasporto<br>";
        content += speseTrasporto.toFixed(2) + "€ imponibile + " + (speseTrasporto * 0.22).toFixed(2) + "€ iva al 22%<br>";
    }
    if (speseIncasso != -1) {
        content += "Spese di incasso<br>";
        content += speseIncasso.toFixed(2) + "€ imponibile + " + (speseIncasso * 0.22).toFixed(2) + "€ iva al 22%<br>";
    }

    var $icon = $(".tooltip-totale");

    $icon.tooltipster({
        content: content,
        contentAsHTML: true,
        trigger: "click",
        interactive: true,
        touchDevices: true,
    });
}

$(".check").on("change", function() {
    let checked = 0;
    $(".check").each(function() {
        if ($(this).is(":checked")) {
            checked = 1;
        }
    });

    if (checked) {
        $("#elimina_righe").removeClass("disabled");
        $("#duplica_righe").removeClass("disabled");
        $("#subtotale_righe").removeClass("disabled");
        $("#confronta_righe").removeClass("disabled");
    } else {
        $("#elimina_righe").addClass("disabled");
        $("#duplica_righe").addClass("disabled");
        $("#subtotale_righe").addClass("disabled");
        $("#confronta_righe").addClass("disabled");
    }
});

$("#check_all").click(function(){
    if( $(this).is(":checked") ){
        $(".check").each(function(){
            if( !$(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    }else{
        $(".check").each(function(){
            if( $(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    }
});

$(".tipo_icon_after").on("change", function() {
    aggiornaInline($(this).closest("tr").data("id"));
});

function aggiornaInline(id) {
    content_was_modified = false;
    var qta = input("qta_"+ id).get();
    var sconto = input("sconto_"+ id).get();
    var tipo_sconto = input("tipo_sconto_"+ id).get();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "update_inline",
            riga_id: id,
            qta: qta,
            sconto: sconto,
            tipo_sconto: tipo_sconto,
        },
        success: function (response) {
            caricaRighe(id);
            renderMessages();
        },
        error: function() {
            caricaRighe(null);
        }
    });
}
init();

function incrementa_riduci($this, value, type) {
    var id_riga = $this.closest("tr").data("id");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "json",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "incrementa_riduci",
            id_riga: id_riga,
            value: value,
            type: type,
        },
        success: function (response) {
            caricaRighe();
            renderMessages();
        },
        error: function() {
            caricaRighe(null);
        }
    });
}
</script>';
