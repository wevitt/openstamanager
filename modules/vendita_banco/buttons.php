<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="btn-group tip" data-toggle="tooltip">
    <button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="fa fa-magic"></i> '.tr('Stampa').'
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">';
    if ($stampante_fiscale->isConfigured()) {
        echo '
        <li>
            <a onclick="stampaDocumento(this, \'xonxoff\', 1)">
                <i class="fa fa-print"></i> '.tr('Scontrino fiscale').'
            </a>
        </li>

        <li>
            <a onclick="stampaDocumento(this, \'xonxoff\', 0)">
                <i class="fa fa-print"></i> '.tr('Scontrino non fiscale').'
            </a>
        </li>

        <li>
            <a onclick="stampaDocumento(this, \'xonxoff\', 0, 0)">
                <i class="fa fa-print"></i> '.tr('Scontrino non fiscale (senza prezzi)').'
            </a>
        </li>';
    }

    if ($stampante_non_fiscale->isConfigured()) {
        echo '
        <li>
            <a onclick="stampaDocumento(this, \'txt\', 0)">
                <i class="fa fa-print"></i> '.tr('Comanda').'
            </a>
        </li>';
    }
    echo '
    </ul>
</div>';

echo '
    <button type="button" id="btn-close" onclick="chiudiDocumento()" '.($numero_righe != 0 ? '' : 'disabled').' class="btn btn-primary '.($is_pagato ? 'hide' : '').'">
        <i class="fa fa-edit"></i> '.tr('Chiudi vendita').'
    </button>

    <button type="button" onclick="apriDocumento()" class="btn btn-warning '.($is_pagato ? '' : 'hide').'">
        <i class="fa fa-edit"></i> '.tr('Riapri vendita').'
    </button>
<div class="clearfix"></div>

<script>
function chiudiDocumento() {
    swal({
        title: "'.tr('Chiudere il documento?').'",
        html: \''.tr('Chiudere la vendita e cambiare lo stato in "Pagato"?').'\',
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $("#closed").val(1);
        $("#submit").trigger("click");
    }).catch(swal.noop);
}

function apriDocumento() {
    swal({
        title: "'.tr('Riaprire il documento?').'",
        html: \''.tr('Riaprire la vendita e cambiare lo stato in "Aperto"?').'\',
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $("#closed").val(0);
        $("#submit").trigger("click");
    }).catch(swal.noop);
}

function stampaDocumento(btn, formato, is_fiscale, show_price = 1) {
    swal({
        title: "'.tr('Stampare il documento?').'",
        html: "'.tr('Sei sicuro di volere stampare il documento?').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        let restore = buttonLoading(btn);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "stampa",
                formato: formato,
                fiscale: is_fiscale,
                show_price: show_price,
            },
            success: function (response) {
                buttonRestore(btn, restore);

                if (response.result) {
                    swal("'.tr('Invio completato').'", response.message, "success").then(() => { parent.window.location.reload(); });
                } else {
                    swal("'.tr('Errore').'", response.message, "error");
                }
            },
            error: function() {
                buttonRestore(btn, restore);

                swal("'.tr('Errore').'", "'.tr('Errore nella stampa del documento').'", "error");
            }
        });
    }).catch(swal.noop);
}
</script>';
