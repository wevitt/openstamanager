<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$obj = Articolo::find($id_record);

echo '
<div class="alert alert-info">
    <h4>'.tr('Gestione della distinta base').'</h4>
    '.tr("Se questo articolo è l'accorpamento di altri articoli presenti allora crea qui la sua composizione").'.
</div>

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <div class="col-md-12 text-right">
        '  .Prints::getLink('Distinta base', $id_record, 'btn-primary text-right', tr('Stampa distinta base'), '|default|').'
        <button class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Sincronizza prezzo acquisto').'", "name": "sincronizza_prezzo_acquisto", "value": "'.$obj->sincronizza_prezzo_acquisto.'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Sincronizza prezzo vendita').'", "name": "sincronizza_prezzo_vendita", "value": "'.$obj->sincronizza_prezzo_vendita.'" ]}
        </div>
    </div>
</form>

<form action="" method="post" id="composizione-form">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="produci">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Quantità').'", "name": "qta_produzione" ]}
        </div>

        <div class="col-md-6">
            <div class="btn-group special" style="margin-top: 25px">
                <button type="button" class="btn btn-warning" onclick="scomponiDistinta()">
                    <i class="fa fa-puzzle-piece"></i> '.tr('Scomponi').'
                </button>

                <button type="button" class="btn btn-info" onclick="componiDistinta()">
                    <i class="fa fa-cog"></i> '.tr('Produci').'
                </button>
            </div>
        </div>
    </div>
</form>

<style>
.btn-group.special {
  display: flex;
}

.special .btn {
  flex: 1
}
</style>

<script>
function scomponiDistinta(){
    let form = $("#composizione-form");

    form.find("input[name=op]").val("scomponi");
    form.submit();
}

function componiDistinta(){
    let form = $("#composizione-form");

    form.find("input[name=op]").val("produci");
    form.submit();
}
</script>

<table class="table table-condensed table-striped table-hover table-bordered" id="tabledistinta">
    <thead>
        <tr>
            <th>
                '.tr('Articolo').'
                <input type="text" class="form-control" id="search_articolo" placeholder="'.tr('Filtra').'...">
            </th>

            <th width="190">
                '.tr('Fornitore').'
                <input type="text" class="form-control" id="search_fornitore" placeholder="'.tr('Filtra').'...">
            </th>

            <th width="90">
                '.tr('Qta').'
                <input type="text" class="form-control" id="search_qta" placeholder="'.tr('Filtra').'...">
            </th>

            <th width="140">
                '.tr('Prezzo di acquisto').'
                <input type="text" class="form-control" id="search_prezzoacquisto" placeholder="'.tr('Filtra').'...">
            </th>

            <th width="140">
                '.tr('Prezzo di vendita').'
                <input type="text" class="form-control" id="search_prezzovendita" placeholder="'.tr('Filtra').'...">
            </th>

            <th width="100"></th>
        </tr>
    </thead>

    <tbody>';

if ($obj) {
    renderDistinta($obj);
}

echo '
        <tr>
            <td colspan="3" class="text-right">
                <b>'.tr('Totale').'</b>
            </td>

            <td class="text-right">
                '.moneyFormat($obj->totale_acquisto).'
            </td>

            <td class="text-right">
                '.moneyFormat($obj->totale_vendita).'
            </td>

            <td></td>
        </tr>
    </tbody>
</table>';

echo '

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update_qta">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Distinte in cui compare questo articolo').'</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 text-right">
                    <button class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped"><br>
                        <tr>
                            <th>
                                '.tr('Distinte').'
                            </th>
                            <th width="180">
                                '.tr('Qta nella distinta').'
                            </th>
                        </tr>';

$parti = $obj->parti;
foreach ($parti as $parte) {
    echo '
                        <tr>
                            <td>'.Modules::link(Modules::get('Articoli')['id'], $parte['id'], $parte['descrizione']).'</td>
                            <td>
                                {[ "type": "number", "name": "qta['.$parte->id.']", "required": 1, "value": "'.$parte->pivot->qta.'" ]}
                        </td>
                  </tr>';
}

echo '
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>';

echo '
<form action="" method="post" id="form-delete">
    <input type="hidden" name="op" value="delete_figlio">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <input type="hidden" name="id_articolo" id="id_articolo" value="">
    <input type="hidden" name="id_figlio" id="id_figlio" value="">
</form>';

echo '
<script>

function delete_articolo(id_parent, id_figlio){
    if(confirm(\'Vuoi eliminare questo articolo\')){
        $("#id_articolo").val(id_parent);
        $("#id_figlio").val(id_figlio);
        $("#form-delete").submit();
    }
}

function add_articolo(id_parent) {
    openModal("'.tr('Aggiungi figlio').'", "'.$structure->fileurl('manage_figlio.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record=" + id_parent);
}

function edit_articolo(id_parent, id_figlio) {
    openModal("'.tr('Modifica figlio').'", "'.$structure->fileurl('manage_figlio.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record=" + id_parent + "&id_articolo=" + id_figlio);
}

$(document).ready(function(){
    $("input[id^=\'search_\']").keyup(function() {
        $("#tabledistinta tr").each(function(){
            $(this).show();
        });
        $("input[id^=\'search_\']").each(function(){
            var position = $(this).closest("th").index();
            var filter = $(this).val().toUpperCase();

            var tr = $("#tabledistinta tr");
            if(filter!=""){
                for (i = 0; i < tr.length; i++) {
                  td = tr[i].getElementsByTagName("td")[position];
                  if (td) {
                    if (td.innerHTML.toUpperCase().indexOf(filter) <= -1) {
                      tr[i].style.display = "none";
                    }
                  }
                }
            }

        });
    });

});
</script>';
