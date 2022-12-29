<?php

    include_once __DIR__.'/../../core.php';

    echo '
    <form actions="" id="previsioni_form" method="post">
        <input type="hidden" name="op" value="manage_previsioni">
        <input type="hidden" name="backto" value="record-list">

        <div class="row">
            <div class="col-md-12 text-right">
                <a type="button" class="btn btn-primary" data-href="'.$structure->fileurl('copy_previsione.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="'.tr('Duplica previsioni').'"><i class="fa fa-clone"></i> '.tr('Duplica previsioni').'</a>
                <a type="button" class="btn btn-primary" data-href="'.$structure->fileurl('add_previsione.php').'" data-toggle="tooltip" data-title="'.tr('Aggiungi previsione').'"><i class="fa fa-plus"></i> '.tr('Aggiungi previsione').'</a>
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-12" id="previsioni">
                <i class="fa fa-gear fa-spin"></i> '.tr('Caricamento').'...
            </div>
        </div>
    </form>
            

    <script>
        $.get("'.$structure->fileurl('ajax_previsioni.php').'", function(response){
            $("#previsioni").html(response);
        });
    </script>';
