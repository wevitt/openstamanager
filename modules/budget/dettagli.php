<?php

include_once __DIR__.'/../../core.php';

$iva_predefinita = $dbo->fetchOne("SELECT percentuale FROM co_iva WHERE id=".prepare(setting('Iva predefinita')))['percentuale'];
$dir = get('dir');
$m = date("m", strtotime(get('periodo')));
$anno = date("Y", strtotime(get('periodo')));
$op = get('op');
$elementi_scaduti = [];

if ($op=='ricavi_reali') {
    $rs = ricavi_reali($m, $anno);
} elseif ($op=='ricavi_previsionali') {
    $rs = ricavi_previsionali($m, $anno);
    for($i=0; $i<count($rs); $i++){
        if( date("Y-m", strtotime($rs[$i]['data'])) < date("Y-m") ){
            $elementi_scaduti[] = $rs[$i];
            unset($rs[$i]);
        }
    }
} elseif ($op=='costi_reali') {
    $rs = costi_reali($m, $anno);
} elseif ($op=='costi_previsionali') {
    $rs = costi_previsionali($m, $anno);
    for($i=0; $i<count($rs); $i++){
        if( date("Y-m", strtotime($rs[$i]['data'])) < date("Y-m") ){
            $elementi_scaduti[] = $rs[$i];
            unset($rs[$i]);
        }
    }
} elseif ($op=='utile_reale') {
    $rs = utile_reale($m, $anno);
} elseif ($op=='utile_previsionale') {
    $rs = utile_previsionale($m, $anno);
} elseif ($op=='entrate_reali') {
    $rs = entrate_reali($m, $anno);
} elseif ($op=='entrate_previsionali') {
    $rs = entrate_previsionali($m, $anno);
    for($i=0; $i<count($rs); $i++){
        if( date("Y-m", strtotime($rs[$i]['data'])) < date("Y-m-d") ){
            $elementi_scaduti[] = $rs[$i];
            unset($rs[$i]);
        }
    }
} elseif ($op=='uscite_reali') {
    $rs = uscite_reali($m, $anno);
} elseif ($op=='uscite_previsionali') {
    $rs = uscite_previsionali($m, $anno);
    for($i=0; $i<count($rs); $i++){
        if( date("Y-m", strtotime($rs[$i]['data'])) < date("Y-m") ){
            $elementi_scaduti[] = $rs[$i];
            unset($rs[$i]);
        }
    }
} elseif ($op=='saldo_reale') {
    $rs = saldo_reale($m, $anno);
} elseif ($op=='saldo_previsionale') {
    $rs = saldo_previsionale($m, $anno);
}

if( !empty($elementi_scaduti) ){
    echo '
    <div class="row">
        <div class="col-md-12">
            <div class="box collapsed-box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">'.tr('Scadute').'</h3>
                    <div class="box-tools pull-right">
                        <button id="btn_fade" type="button" class="btn btn-box-tool" data-widget="collapse" onclick="mostra();">
                        <i class="fa fa-plus"></i>
                        </button>
                    </div>   
                </div>
                
                <div id="box_scaduti" class="box-body collapse">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th width="10%">'.tr('Data').'</th>
                                <th width="30%">'.tr('Descrizione').'</th>
                                <th width="30%">'.tr('Conto').'</th>
                                <th>'.tr('Anagrafica').'</th>
                                <th width="10%">'.tr('Importo').'</th>
                            </tr>';

                        foreach($elementi_scaduti as $elemento) {
                            echo '
                            <tr>
                                <td class="text-center">
                                    '.Translator::dateToLocale($elemento['data']).'
                                </td>
                                <td>
                                    '.nl2br($elemento['descrizione']).'
                                </td>
                                <td>
                                    '.$elemento['conto'].'
                                </td>
                                <td>
                                    '.nl2br($elemento['anagrafica']).'
                                </td>
                                <td class="text-right">
                                    '.moneyFormat($elemento['totale']).'
                                </td>
                            </tr>';
                        }

                        echo '
                        </table>
                    </div>
                </div>
            </div>   
        </div>
    </div>';
}

if (empty($rs)) {
    echo '
    <div class="row">
        <div class="col-md-12">
            <span>'.tr('Nessun dettaglio visibile').'</span>
        </div>
    </div>';
} else {
    echo '
    <table class="table table-bordered table-striped">
        <tr>
            <th width="10%">'.tr('Data').'</th>
            <th width="30%">'.tr('Descrizione').'</th>
            <th width="30%">'.tr('Conto').'</th>
            <th>'.tr('Anagrafica').'</th>
            <th width="10%">'.tr('Importo').'</th>
        </tr>';
    for ($i=0; $i<count($rs); $i++) {
        if( !empty($rs[$i]) ){
            echo '
                <tr>
                    <td class="text-center">
                        '.Translator::dateToLocale($rs[$i]['data']).'
                    </td>
                    <td>
                        '.nl2br($rs[$i]['descrizione']).'
                    </td>
                    <td>
                        '.$rs[$i]['conto'].'
                    </td>
                    <td>
                        '.nl2br($rs[$i]['anagrafica']).'
                    </td>
                    <td class="text-right">
                        '.moneyFormat($rs[$i]['totale']).'
                    </td>
                </tr>';
        }
    }
    echo '
    </table>';
}

?>

<script>

    function mostra(){
        if( !$('#box_scaduti').is(":visible") ){
            $('#box_scaduti').fadeIn(400, function () {
                $("#btn_fade").html("<i class='fa fa-minus'></i>");
            });
        }else{
            $('#box_scaduti').fadeOut(400, function () {
                $("#btn_fade").html("<i class='fa fa-plus'></i>");
            });
        }
    }

</script>