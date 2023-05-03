<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Dati').'</h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "span", "label": "'.tr('Nome').'", "name": "nome", "value": "'.$attributo->nome.'", "help": "'.tr("Nome univoco dell'attributo").'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Titolo').'", "name": "titolo", "value": "'.$attributo->titolo.'", "required": 1, "help": "'.tr("Nome visibile dell'attributo").'" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="box box-primary">
    <div class="box-header">
        <h3 class="box-title">'.tr('Valori attributo').'</h3>
    </div>

    <div class="box-body">
        <button type="button" class="btn btn-primary pull-right" onclick="aggiungiValore(this)">
            <i class="fa fa-plus"></i> '.tr('Aggiungi valore').'
        </button>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>'.tr('Valore').'</th>
                    <th width="10%" class="text-center">#</th>
                </tr>
            </thead>

            <tbody>';

$valori = $attributo->valori;
foreach ($valori as $valore) {
    echo '
                <tr data-id="'.$valore->id.'">
                    <td>'.$valore->nome.'</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-warning btn-xs" onclick="modificaValore(this)">
                            <i class="fa fa-edit"></i>
                        </button>

                        <button type="button" class="btn btn-danger btn-xs" onclick="rimuoviValore(this)">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    </td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>

<script>
function aggiungiValore(button) {
    let riga = $(button).closest("tr");

    // Apertura modal
    openModal("'.tr('Aggiungi valore').'", "'.$module->fileurl('gestione-valore.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record);
}

function modificaValore(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");

    // Apertura modal
    openModal("'.tr('Modifica valore').'", "'.$module->fileurl('gestione-valore.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_valore=" + id);
}

function rimuoviValore(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");

    // Redirect
    redirect(globals.rootdir + "/editor.php", {
       id_module: globals.id_module,
       id_record: globals.id_record,
       id_valore: id,
       op: "rimuovi-valore",
       backto: "record-edit",
   });
}
</script>';
