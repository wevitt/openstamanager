<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="row">
    <div class="col-md-6">
        {["type": "text", "name": "barcode", "label": "'.tr('Barcode').'", "value": "", "extra": "autofocus" ]}
    </div>
    <div class="col-md-6">
        {["type": "text", "name": "codice", "label": "'.tr('Codice').'", "value": "" ]}
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        {["type": "text", "name": "descrizione", "label": "'.tr('Descrizione').'", "value": "" ]}
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary" onclick="cerca();"><i class="fa fa-search"></i> '.tr('Cerca').'</button>
    </div>
</div>

<div class="row">
    <div class="col-md-12 text-center" id="modal_articoli">
    </div>
</div>';

?>

<script>
    $("#modals > div").on("shown.bs.modal", function(){
        $("#barcode").focus();
    });

    $("#barcode").keyup(function(){
        if($(this).val()){
            $("#codice").prop("disabled",true);
            $("#descrizione").prop("disabled",true);
        }else{
            $("#codice").prop("disabled",false);
            $("#descrizione").prop("disabled",false);
        }
    });

    $("#codice").keyup(function(){
        if($(this).val()){
            $("#barcode").prop("disabled",true);
            $("#descrizione").prop("disabled",true);
        }else{
            $("#barcode").prop("disabled",false);
            $("#descrizione").prop("disabled",false);
        }
    });

    $("#descrizione").keyup(function(){
        if($(this).val()){
            $("#codice").prop("disabled",true);
            $("#barcode").prop("disabled",true);
        }else{
            $("#codice").prop("disabled",false);
            $("#barcode").prop("disabled",false);
        }
    });

    function cerca(){
        $('#modal_articoli').html("<i class=\"fa fa-gear fa-spin\"></i> <?php echo tr('Caricamento'); ?>...");
        $('#modal_articoli').load('<?php echo $rootdir; ?>/modules/vendita_easy/ajax_articoli.php?id_record=<?php echo get('id_record'); ?>&barcode='+$("#barcode").val()+'&codice='+$("#codice").val()+'&descrizione='+$("#descrizione").val());
        setTimeout(function(){
            $("#modal_articoli").prepend("<hr>");
        },300);
    }

</script>
