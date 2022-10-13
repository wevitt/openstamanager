<?php
    include_once __DIR__.'/../../../core.php';
    include_once __DIR__.'/../../../../core.php';

    $id_anagrafica = get('id_anagrafica');
    $base_path = get('base_path')
?>

<input type="hidden" class="id_anagrafica" value="<?= $id_anagrafica ?>">
<input type="hidden" class="base_path" value="<?= $base_path ?>">

<h3><?= tr('Dettagli cliente') ?></h3>
<div class="dettaglio-utente col-12">

</div>

<script>
    $(document).ready(function() {
        var id_anagrafica = $('.id_anagrafica').val();
        var base_path = $('.base_path').val();

        $.get(base_path + "/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + id_anagrafica, function(data){
            $(".dettaglio-utente").html(data);
        });
    });
</script>
