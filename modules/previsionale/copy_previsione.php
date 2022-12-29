<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="'.ROOTDIR.'/controller.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="copy_previsione">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">

    <br>
    <div class="row">
        <div class="col-md-8 text-center">
            <span class="text-center"><b>'.tr('Inserire il numero di anni da aggiungere alle previsioni:').'</b></span>
            <br>('.tr('Esempio: inserire 2 per copiare le previsioni in avanti di 2 anni').')
        </div>
        <div class="col-md-4">
            {["type":"number", "name":"year", "value":"1", "required":1 ]}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-right" style="margin-top:15px;">
            <button type="submit" class="btn btn-primary"><i class="fa fa-clone"></i> '.tr('Duplica').'</button>
        </div>
    </div>
</form>';
