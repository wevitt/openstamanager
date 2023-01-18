<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$plugin_distinte = $dbo->fetchNum("SELECT * FROM zz_plugins WHERE name='Distinta base'");

$id_ordine = get('idordine');
$id_fornitore = get('idfornitore');
$tipo_fornitore = get('fornitore');

echo '
<form action="" method="post" id="form_crea_ordine">
    <input type="hidden" name="op" value="crea_ordine">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">';
if ($plugin_distinte) {
    echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Filtro per fornitore').'", "name": "idfornitore", "ajax-source": "fornitori", "value": "'.$id_fornitore.'" ]}
        </div>
        
        <div class="col-md-9 text-right" style="margin-top:27px;">            
            <a class="btn btn-'.($tipo_fornitore == 'piu_economico' ? 'warning' : 'info').' btn-sm" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'&fornitore=piu_economico">
                <i class="fa fa-angle-down"></i>'.tr('Prezzo più economico').'
            </a>
            
            <a type="button" class="btn btn-'.($tipo_fornitore == 'piu_alto' ? 'warning' : 'info').' btn-sm" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'&fornitore=piu_alto">
                <i class="fa fa-angle-up"></i> '.tr('Prezzo più alto').'
            </a>
            
            <a type="button" class="btn btn-'.($tipo_fornitore == 'piu_rapido' ? 'warning' : 'info').' btn-sm" href="'.ROOTDIR.'/controller.php?id_module='.$id_module.'&fornitore=piu_rapido">
                <i class="fa fa-clock-o"></i> '.tr('Tempi di consegna più rapidi').'
            </a>
        </div>';
}

echo '
    </div>
    <br>';

// Elenco articoli di livello massimo richiesti
$query_articoli = "SELECT 
    idarticolo AS id_articolo, 
    SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) as qta_necessaria
FROM or_righe_ordini
    INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine
WHERE
    idtipoordine = (SELECT id FROM or_tipiordine WHERE dir = 'entrata') AND
    idstatoordine IN (SELECT id FROM or_statiordine WHERE impegnato=1) AND
    or_righe_ordini.qta > or_righe_ordini.qta_evasa AND
    idarticolo!=0
GROUP BY or_righe_ordini.idarticolo";
$componenti_necessari = $dbo->fetchArray($query_articoli);

// Individuazione articoli richiesti
$componenti = [];
foreach ($componenti_necessari as $riga) {
    $articolo = Articolo::find($riga['id_articolo']);

    $componenti[] = [
        'articolo' => $articolo,
        'qta_necessaria' => $riga['qta_necessaria'],
    ];
}

$articoli = scomponi_articoli($componenti);

echo '
    <h3>'.tr('Articoli da ordinare').'</h3>';

if (!empty($articoli)) {
    echo '
    <table class="table table-condensed table-striped table-bordered" id="table" style="border-left: 10px solid #297fcc;">
        <thead>
            <tr>
                <th><input type="checkbox" class="selectall"></th>
                <th>'.tr('Articolo').'</th>
                <th>'.tr('Q.tà minima').'</th>
                <th>'.tr('Q.tà a magazzino').'</th>
                <th>'.tr('Q.tà già ordinata').'</th>
                <th>'.tr('Q.tà da ordinare').'</th>
                <th width="300">'.tr('Fornitore').'</th>
            </tr>
        </thead>

        <tbody>';

    foreach ($articoli as $articolo) {
        renderArticolo($articolo['articolo'], $articolo['qta_necessaria'], $articolo['qta_disponibile']);
    }

    echo '
        </tbody>
    </table>';
} else {
    echo '
    <p>'.tr('Nessun articolo da ordinare').'</p>';
}

$componenti_sottoscorta = $dbo->fetchArray('SELECT id AS id_articolo, threshold_qta AS qta_necessaria FROM mg_articoli WHERE servizio = 0 AND qta < threshold_qta');

// Individuazione articoli richiesti
$componenti = [];
foreach ($componenti_sottoscorta as $riga) {
    $articolo = Articolo::find($riga['id_articolo']);

    $componenti[] = [
        'articolo' => $articolo,
        'qta_necessaria' => $riga['qta_necessaria'],
    ];
}

$articoli = scomponi_articoli($componenti);

// Articoli che non sono distinte sotto scorta
echo '
    <h3>'.tr('Articoli sottoscorta').'</h3>';

if (!empty($articoli)) {
    echo '
    <table class="table table-condensed table-striped table-bordered" id="table" style="border-left: 10px solid #cc2929;">
        <thead>
            <tr>
                <th><input type="checkbox" class="selectall"></th>
                <th>'.tr('Articolo').'</th>
                <th>'.tr('Q.tà minima').'</th>
                <th>'.tr('Q.tà a magazzino').'</th>
                <th>'.tr('Q.tà già ordinata').'</th>
                <th>'.tr('Q.tà da ordinare').'</th>
                <th width="300">'.tr('Fornitore').'</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($articoli as $articolo) {
        renderArticolo($articolo['articolo'], $articolo['qta_necessaria'], $articolo['qta_disponibile']);
    }

    echo '
        </tbody>
    </table>';
} else {
    echo '
    <p>'.tr('Nessun articolo da ordinare').'</p>';
}



echo '
    <button type="button" class="btn btn-primary btn-lg" id="button_create" onclick="crea_ordine();">
        <i class="fa fa-plus"></i> '.tr('Crea ordine fornitore').'
    </button>
</form>

<style>
#button_create {
    position:fixed;
    bottom:10px;
    width:300px;
    left:50%;
    margin-right:35px;
}
</style>

<script>
    $(document).ready(function() {
         $(".selectall").click(function(){
            var checked = $(this).is(":checked");

            $(this).closest("table").find(".check").each(function(){
                $(this).prop("checked", checked);
            });
        });
         
        $(".check").change(function() {
            var checked = this.checked;
            $(this).closest("tr").find("select").each(function(){
                $(this).prop("required", checked);
            });
        });

        $("#idfornitore").change(function(){
            var id_fornitore = $(this).val() != null ? $(this).val() : "";
            location.href = globals.rootdir + "/controller.php?id_module='.$id_module.'&idfornitore=" + id_fornitore;
        });
    });

    function crea_ordine(){
        if($(".check:checked").length != 0) {
            if($("#form_crea_ordine").parsley().validate()) {
                swal({
                    title: "'.tr("Creare l'Ordine fornitore?").'",
                    html: "'.tr("Desideri procedere alla creazione dell'Ordine fornitore per questi articoli?").'",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "'.tr('Procedi').'"
                }).then(function (result) {
                    $("#form_crea_ordine").submit();
                });
            }
        } else {
            swal("'.tr('Errore').'", "'.tr('Nessun articolo selezionato!').'", "error");
        }
    }
</script>';
