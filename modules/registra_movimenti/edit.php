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

use Modules\Anagrafiche\Anagrafica;

if(!empty($records[0]['id'])){
    $anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
    echo '
    <div class="row">
        <div class="col-md-3">
            {[ "type": "select", "name": "banca", "ajax-source": "banche", "placeholder":" Seleziona Banca", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).']}
        </div>
        <div class="col-md-offset-2 col-md-2 text-right">
            <form action="" id="form-compila" method="post">
                <input type="hidden" name="op" value="compila">
                <button type="button" class="btn btn-primary" onclick="compila()">
                    <i class="fa fa-list-alt"></i>'.tr(' Tenta compilazione automatica').'
                </button>
            </form>
        </div>
        <div class="col-md-5 text-right">
            <form action="'.base_path().'/editor.php?id_module='.$id_module.'" id="rimuovi_all" method="post">
                <input type="hidden" name="op" value="rimuovi_all">

                <button type="button" class="btn btn-danger" onclick="rimuovi()">
                    <i class="fa fa-times"></i>'.tr(' Rimuovi movimenti').'
                </button>
                </form>
        </div>
    </div>
    <br>
    <table id="table-serials" class="table table-bordered table-condensed table-striped datatables" style="table-layout:fixed;"> 
        <col width="8%"><col width="38%"><col width="8%"><col width="39%"><col width="7%">
        <thead>
            <tr>
                <th class="text-center">'.tr('DATA').'</th>
                <th class="text-center">'.tr('DESCRIZIONE').'</th>
                <th class="text-center">'.tr('IMPORTO').'</th>
                <th class="text-center">'.tr('SCADENZA').'</th>
                <th class="text-center">#</th>
            </tr>
        </thead>
        <tbody>';
        foreach($records as $record){
            $codice_abi = $dbo->fetchOne('SELECT * FROM co_movimenti_abi WHERE codice='.prepare($record['codice_abi']));
            if($record['importo']>0){
                $color = 'green;';
            } else{
                $color = 'red;';
            }
            echo '
            <tr class="riga-'.$record['id'].'">
                <td class="text-center" style="vertical-align:middle">
                    '.dateFormat($record['data']).'
                </td>

                <td>
                    <small>
                        '.$record['descrizione'].'
                        '.($codice_abi ? ($codice_abi['id_modello'] ? '<br><b>' : '<span class="help-block">').''.$codice_abi['codice'].' - '.$codice_abi['descrizione'].''.($codice_abi['id_modello'] ? '</b>' : '</span>') : '').'
                    </small>
                </td>

                <td class="text-center" style="vertical-align:middle">
                    <span style="color:'.$color.'">'.numberFormat($record['importo'], 2).' €</span>
                </td>

                <td style="vertical-align:middle">
                    {[ "type": "select", "multiple": "1", "name": "documento", "class": "documento", "id": "documento-'.$record['id'].'", "ajax-source": "scadenze", "select-options": '.json_encode(['id' => $record['id']]).']}
                </td>

                <td style="vertical-align:middle">
                    <form action="" id="form-ignora-'.$record['id'].'" method="post">
                        <input type="hidden" name="op" value="ignora">
                        <input type="hidden" name="id_record" value="'.$record['id'].'">
                    </form>
                    <div class="btn-group btn-group-flex">
                        <button type="button" class="btn btn-primary btn-xs" onclick="registra(\''.$record['id'].'\')">
                            <i class="fa fa-euro"></i> '.tr('Registra').'
                        </button>
                        <button type="button" class="btn btn-danger btn-xs" onclick="ignora(\''.$record['id'].'\');">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>';
        }
        echo '
        </tbody>
    </table>';
} else{
    echo '<br><br><br><br><br><h2 class="text-center text-muted">Nessun movimento da registrare</h2><br><br><br>';
}
echo '
<script>
    function registra(id){
        let documento = $("#documento-"+ id).val();
        doc = documento.join(",");
        let banca = $("#banca").val();
        openModal("'.tr('Registra').'", "'.$module->fileurl('add_prima_nota.php').'?id_module=" + globals.id_module + "&id_record=" + id +"&documento="+ doc +"&single=1&banca="+ banca);
    }

    function ignora(id){
        if(confirm("Vuoi scartare questo movimento?")){
            submitAjax("#form-ignora-"+id, {}, function(data) {
                $.ajax({
                    url: globals.rootdir + "/actions.php",
                    cache: false,
                    type: "POST",
                    data: {
                        id_module: globals.id_module,
                        id_record: id,
                        op: "ignora",
                    },
                    success: function(data) {
                        $(".riga-"+ id).fadeOut();
                    },
                    error: function(data) {
                    }
                });
            })
        }
    }

    function compila(){
        if(confirm("Vuoi compilare i campi in automatico?")){
            $(".documento").selectClear();
            submitAjax("#form-compila", {}, function(data) {
                $.ajax({
                    url: globals.rootdir + "/actions.php",
                    cache: false,
                    type: "POST",
                    data: {
                        id_module: globals.id_module,
                        op: "compila",
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        Object.keys(data).forEach(function(key) {
                            $("#documento-"+ key).selectSetNew(data[key]["id"], data[key]["descrizione"]);
                        });
                    },
                    error: function(data) {
                    }
                });
            })
        }
    }

    function rimuovi(){
        if(confirm("Vuoi eliminare tutti i movimenti?")){
            $("#rimuovi_all").submit();
        }
    }

</script>';
