<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

use Modules\Interventi\Intervento;

echo '
<link rel="stylesheet" type="text/css" media="all" href="'.$structure->fileurl('assets/dist/css/style.css').'"/>';

echo '
<table class="table table-striped table-hover table-condensed table-articoli">
    <thead>
        <tr>
            <th width="21%" height="25px" class="text-center">'.tr('Q.tà').'</th>
            <th width="30%">'.tr('Descrizione').'</th>
            <th width="12%" class="text-center">'.tr('Prezzo').'</th>
            <th width="17%" class="text-center">'.tr('Subtotale').'</th>';
if (!$documento->isPagato() && empty($documento->iddocumento) && empty($documento->data_emissione)) {
    echo '
                <th width="15%" class="text-center">#</th>';
}
echo '
        </tr>
    </thead>
    <tbody>';

// Righe documento
$righe = $documento->getRighe();
$n_righe = 0;

foreach ($righe as $riga) {
    ++$n_righe;
    $reparto = $database->fetchOne('SELECT * FROM vb_reparti WHERE id = '.prepare($riga->id_reparto));

    if ($riga->isDescrizione()) {
        $descrizione = nl2br($riga->descrizione);

        echo '
        <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'">
            <td colspan="4">
                '.$descrizione.'
                <span class="pull-right">['.($reparto ? $reparto['codice'] : tr('Reparto mancante')).']</span>
            </td>';
        // Possibilità di modificare le riga solo se la vendita non è pagata!
        if (!$documento->isPagato() && empty($documento->iddocumento) && empty($documento->data_emissione)) {
            echo '
                    <td class="text-center">
                        <a class="btn btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a class="btn btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>';
        }
    } else {
        echo '
        <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'">
            <td height="25px" class="text-center">';

        // Quantità e unità di misura
        if (!$documento->isPagato() && empty($documento->iddocumento) && empty($documento->data_emissione)) {
            echo '
                    <button class="btn btn-sm btn-info pull-left" title="'.tr('Decrementa quantità').'" onclick="decrementaRiga(this)">
                        <i class="fa fa-minus"></i>
                    </button>';
        }
        echo Translator::numberToLocale($riga->qta, 'qta').' '.$riga->um;

        if (!$documento->isPagato() && empty($documento->iddocumento) && empty($documento->data_emissione)) {
            echo '
                    <button class="btn btn-sm btn-success pull-right" title="'.tr('Incrementa quantità').'" onclick="incrementaRiga(this)">
                        <i class="fa fa-plus"></i>
                    </button>';
        }

        echo '
        </td>';

        // Descrizione
        echo '
        <td class="text-left">';

        $descrizione = nl2br($riga->descrizione);
        if ($riga->isArticolo()) {
            $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->articolo->codice.' - '.$descrizione, null);
        }
        echo '
                '.$descrizione.'
                <span class="pull-right">['.($reparto ? $reparto['codice'] : tr('Reparto mancante')).']</span>
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

        // Prezzi unitari
        echo '
            <td class="text-right">
                '.(empty($riga->prezzo_unitario_corrente) ? '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' : '').'
                '.moneyFormat($riga->prezzo_unitario_corrente);

        // Importo
        echo '
            <td class="text-right">
                '.moneyFormat($riga->importo).'
            </td>';

        // Possibilità di modificare le riga solo se la vendita non è pagata!
        if (!$documento->isPagato() && empty($documento->iddocumento) && empty($documento->data_emissione)) {
            echo '
                <td class="text-center">
                    <a class="btn btn-sm btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </a>
                    <a class="btn btn-sm btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            </tr>';
        }

        echo '
            <script>
                $("#btn_cancella").prop("disabled",false);
            </script>';
    }
}

if ($n_righe == 0) {
    echo '
    <tr>
        <td colspan="5">'.tr('Nessun articolo aggiunto').'!</td>
    </tr>';

    echo '
    <script>
        $(document).ready(function(){
            $("#btn_cancella").prop("disabled",true);
        });
    </script>';
}

echo '
    </tbody>';

echo '
</table>';

echo '
<script>
$(document).ready(function() {
    $(".sconto-variabile input, .sconto-variabile select").on("change", function() {
        aggiornaSconto(this);
    });
});

function aggiornaSconto(input) {
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
            caricaContenuti();
        },
        error: function() {
            caricaContenuti();
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
            url: globals.rootdir + "/actions.php?id_module='.Modules::get('Vendita al banco')['id'].'",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: '.Modules::get('Vendita al banco')['id'].',
                id_record: globals.id_record,
                op: "delete_riga",
                riga_type: type,
                riga_id: id,
            },
            success: function (response) {
                caricaContenuti();
                $(button).prop("disabled", false);
            },
            error: function() {
                caricaContenuti();
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
            id_module: '.Modules::get('Vendita al banco')['id'].',
            id_record: globals.id_record,
            op: "decrementa_riga",
            riga_type: type,
            riga_id: id,
        },
        success: function (response) {
            caricaContenuti().then(function() {
                evidenziaRiga(id, "E08E0B");
                $(button).prop("disabled", false);
            });
        },
        error: function() {
            caricaContenuti();
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
            id_module: '.Modules::get('Vendita al banco')['id'].',
            id_record: globals.id_record,
            op: "incrementa_riga",
            riga_type: type,
            riga_id: id,
        },
        success: function (response) {
            caricaContenuti().then(function() {
                evidenziaRiga(id, "008D4C");
                $(button).prop("disabled", false);
            });
        },
        error: function() {
            caricaContenuti();
            $(button).prop("disabled", false);
        }
    });
}

function evidenziaRiga(id, color) {
    $("tr[data-id=" + id + "]").effect("highlight", {color: "#" + color}, 2000);
}

</script>';
