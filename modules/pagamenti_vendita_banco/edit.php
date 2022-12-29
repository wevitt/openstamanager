<?php

include_once __DIR__.'/../../core.php';

unset($_SESSION['superselect']['tipologia_pagamento']);
$_SESSION['superselect']['tipologia_pagamento'] = $id_record;

echo '
<!-- DATI -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Dati').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                {[ "type": "text", "label": "'.tr('Tipologia').'", "name": "tipologia", "readonly": 1, "value": "$tipologia$" ]}
            </div>
        </div>
    </div>
</div>';

echo '
<!-- ELEMENTI -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Elementi collegati').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "select", "label": "'.tr('Elemento da collegare').'", "name": "id_element", "required": 1, "ajax-source": "pagamenti_vendite" ]}
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary" onclick="aggiungiRiga(this)" style="margin-top: 25px">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>
            </div>
        </div>

        <div id="elementi_collegati"></div>
    </div>
</div>

<script>
function reloadRows() {
    $("#main_loading").fadeIn();
    $("#elementi_collegati").load("'.$module->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function() {
        $("#main_loading").fadeOut();
    });
}

function aggiungiRiga(btn) {
    var element_input = $("#id_element");
    var id_element = element_input.val();
    element_input.selectReset();

    var tipologia = $("#tipologia").val();

    if (id_element) {
        var restore = buttonLoading(btn);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "add_element",
                id_element: id_element,
                tipologia: tipologia,
            },
            success: function (response) {
                buttonRestore(btn, restore);

                swal("'.tr('Elemento collegato!').'", "", "success");
                reloadRows();
            },
            error: function() {
                buttonRestore(btn, restore);

                swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio della relazione').'", "error");
            }
        });
    }
}

function rimuoviRiga(button) {
    var tipologia = $("#tipologia").val();

    swal({
        title: "'.tr('Rimuovere questa riga?').'",
        html: "'.tr('Sei sicuro di volere rimuovere questa riga?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        var riga = $(button).closest("tr");
        var id = riga.data("id");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "remove_element",
                id_element: id,
                tipologia: tipologia,
            },
            success: function (response) {
                reloadRows();
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio della relazione').'", "error");
            }
        });
    }).catch(swal.noop);
}

$(document).ready(reloadRows);
</script>';
