<?php

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Modules\Statistiche\Stats;

$mesi = [ '', tr('Gen'), tr('Feb'), tr('Mar'), tr('Apr'), tr('Mag'), tr('Giu'), tr('Lug'), tr('Ago'), tr('Set'), tr('Ott'), tr('Nov'), tr('Dic') ];

$start = new Carbon($_SESSION['period_start']);
$end = new Carbon($_SESSION['period_end']);
$iva_predefinita = $dbo->fetchOne("SELECT percentuale FROM co_iva WHERE id=".prepare(setting('Iva predefinita')))['percentuale'];

echo '
<script src="'.$rootdir.'/assets/dist/js/chartjs/Chart.min.js"></script>
<script src="'.$rootdir.'/modules/statistiche/js/functions.js"></script>
<script src="'.$rootdir.'/modules/statistiche/js/calendar.js"></script>
<script src="'.$rootdir.'/modules/statistiche/js/manager.js"></script>
<script src="'.$rootdir.'/modules/statistiche/js/stat.js"></script>
<script src="'.$rootdir.'/modules/statistiche/js/stats/line_chart.js"></script>
<script src="'.$rootdir.'/modules/budget/js/chartjs-plugin-annotation.min.js"></script>
';

?>

<!-- ANDAMENTO ECONOMICO -->
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-exchange"></i> <?php echo tr('Andamento economico'); ?></h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body collapse in" style="overflow-y:auto;">
        <canvas id="andamento_economico" height="100"></canvas>
        <hr>
        <table class="table table-condensed table-bordered" style="table-layout:fixed;">
            <thead>
                <tr>
                    <th width="24"></th>
                    <th width="130"></th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '<th width="100">'.strtoupper($mesi[ $mese->format('n') ]).'</th>';

                        $mese->addMonth()->format('n');
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th width="110" class="text-right">
                        <?php echo strtoupper(tr('Totale')); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <!-- RICAVI REALI -->
                <tr>
                    <td id="td-ricavi" rowspan="3" class="text-center">
                        <i class="fa fa-chevron-circle-up text-success"></i>
                    </td>

                    <td class="text-right">
                        <div class="pull-left">
                            <button class="btn btn-default btn-xs" onclick="show_ricavi()"><i class="plus-ricavi fa fa-plus"></i></button>
                        </div>
                        <span class="tip" title="<?php echo tr('Ricavi realizzati e già consolidati'); ?>"><?php echo strtoupper(tr('Ricavi')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_ricavi = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = ricavi_reali($mese->format('n'), $anno);
        
                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right success text-success">
                                <a class="text-success" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=ricavi_reali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right success text-success">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_ricavi[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                    <?php echo moneyFormat(sum(array_values($totale_ricavi)), 2); ?>
                    </th>
                </tr>
                
                    <?php
                    $conti = ricavi_reali_gruppi($start, $end);
                    $ricavi_mensili = [];
                    
                    foreach($conti as $conto){
                        $ricavi_mensili[$i][$conto['conto']][$conto['mese']][$conto['anno']] += $conto['totale'];
                    }
                    
                    foreach($ricavi_mensili as $i => $ricavi){
                        foreach($ricavi as $conto => $ricavo_mensile){
                            $totale_conti = [];
                            $mese = $start->copy();
                            $anno = $mese->format('Y');
                            
                            echo '
                            <tr class="ricavi hide">
                                <td class="text-right success text-success">
                                    '.$conto.'
                                </td>';

                            for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {

                                echo '
                                <td class="text-right">
                                    '.($ricavo_mensile[$mese->format('n')][$anno] ? moneyFormat($ricavo_mensile[$mese->format('n')][$anno], 2) : '-').'
                                </td>';

                                $totale_conti[$anno.'-'.$mese->format('n')] = $ricavo_mensile[$mese->format('n')][$anno];
                                $mese->addMonth();
                                $anno = $mese->format('Y');
                            }

                            echo '
                            <th class="text-right">
                                '.moneyFormat(sum(array_values($totale_conti)), 2).'
                                </th>
                            </tr>';
                        }
                    }
                    ?>

                <!-- RICAVI PREVISIONALI -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Ricavi previsti da previsionali o sorgenti dati esterne'); ?>"><?php echo strtoupper(tr('Prev. ricavi')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_ricavi_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = ricavi_previsionali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">
                                <a class="'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=ricavi_previsionali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_ricavi_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                    <?php echo moneyFormat(sum(array_values($totale_ricavi_prev)), 2); ?>
                    </th>
                </tr>

                <!-- RICAVI TOTALI -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma dei ricavi realizzati e dei ricavi previsionali'); ?>"><?php echo strtoupper(tr('Ricavi totali')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right success text-success">
                            <strong>'.moneyFormat(sum($totale_ricavi[$anno.'-'.$mese->format('n')], $totale_ricavi_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                        <?php echo moneyFormat(sum(array_values($totale_ricavi), array_values($totale_ricavi_prev)), 2); ?>
                    </th>
                </tr>



                <!-- COSTI REALI -->
                <tr>
                    <td id="td-costi" rowspan="3" class="text-center">
                        <i class="fa fa-chevron-circle-down text-danger"></i>
                    </td>

                    <td class="text-right">
                        <div class="pull-left">
                            <button class="btn btn-default btn-xs" onclick="show_costi()"><i class="plus-costi fa fa-plus"></i></button>
                        </div>
                        <span class="tip" title="<?php echo tr('Costi sostenuti già consolidati'); ?>"><?php echo strtoupper(tr('Costi')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_costi = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = costi_reali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right danger text-danger">
                                <a class="text-danger" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=costi_reali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right danger text-danger">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_costi[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                    <?php echo moneyFormat(sum(array_values($totale_costi)), 2); ?>
                    </th>
                </tr>

                <?php
                    $conti = costi_reali_gruppi($start, $end);
                    $costi_mensili = [];
                 
                    foreach($conti as $conto){
                        $costi_mensili[$i][$conto['conto']][$conto['mese']][$conto['anno']] += $conto['totale'];
                    }
                    foreach($costi_mensili as $i => $costi){
                        foreach($costi as $conto => $costo_mensile){
                            $totale_conti = [];
                            $mese = $start->copy();
                            $anno = $mese->format('Y');
                            
                            echo '
                            <tr class="costi hide">
                                <td class="text-right danger text-danger">
                                    '.$conto.'
                                </td>';

                            for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                                
                                echo '
                                <td class="text-right">
                                    '.($costo_mensile[$mese->format('n')][$anno] ? moneyFormat($costo_mensile[$mese->format('n')][$anno], 2) : '-').'
                                </td>';

                                $totale_conti[$anno.'-'.$mese->format('n')] = $costo_mensile[$mese->format('n')][$anno];
                                $mese->addMonth();
                                $anno = $mese->format('Y');
                            }

                            echo '
                            <th class="text-right">
                                '.moneyFormat(sum(array_values($totale_conti)), 2).'
                                </th>
                            </tr>';
                        }
                    }
                    ?>

                <!-- COSTI PREVISIONALI -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Costi previsti da previsionali o sorgenti dati esterne'); ?>"><?php echo strtoupper(tr('Prev. costi')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_costi_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = costi_previsionali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right danger text-danger">
                                <a class="text-danger" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=costi_previsionali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right danger text-danger">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_costi_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                        <?php echo moneyFormat(sum(array_values($totale_costi_prev)), 2); ?>
                    </th>
                </tr>

                <!-- COSTI TOTALI -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma dei costi sostenuti e previsionali'); ?>"><?php echo strtoupper(tr('Costi totali')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');
                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right danger text-danger">
                            <strong>'.moneyFormat(sum($totale_costi[$anno.'-'.$mese->format('n')], $totale_costi_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                        <?php echo moneyFormat(sum(array_values($totale_costi), array_values($totale_costi_prev)), 2); ?>
                    </th>
                </tr>




                <!-- UTILE REALE -->
                <tr>
                    <td rowspan="3" class="text-center">
                        <i class="fa fa-line-chart text-primary"></i>
                    </td>

                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Utile generato già consolidato'); ?>"><?php echo strtoupper(tr('Utile')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_utile = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = utile_reale($mese->format('n'), $anno);

                        echo '<td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : ' danger text-danger').'">'
                            .moneyFormat($movimenti[0]['importo'], 2).
                        '</td>';

                        $totale_utile[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right<?php echo (sum(array_values($totale_utile))) ? ' success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile)), 2); ?>
                    </th>
                </tr>

                <!-- UTILE PREVISIONALE -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Utile previsto in base a ricavi e costi previsionali'); ?>"><?php echo strtoupper(tr('Prev. utile')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_utile_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = utile_previsionale($mese->format('n'), $anno);

                        echo '<td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : ' danger text-danger').'">'
                            .moneyFormat($movimenti[0]['importo'], 2).
                        '</td>';

                        $totale_utile_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right<?php echo (sum(array_values($totale_utile_prev)) >= 0) ? ' success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile_prev)), 2); ?>
                    </th>
                </tr>

                <!-- UTILE TOTALE -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma dell\'utile generato e dell\'utile previsto'); ?>"><?php echo strtoupper(tr('Utile totale')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right'.((sum($totale_utile[$anno.'-'.$mese->format('n')], $totale_utile_prev[$anno.'-'.$mese->format('n')]) >= 0) ? ' success text-success' : ' danger text-danger').'">
                            <strong>'.moneyFormat(sum($totale_utile[$anno.'-'.$mese->format('n')], $totale_utile_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right <?php echo (sum(array_values($totale_utile), array_values($totale_utile_prev))>=0) ? 'success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile), array_values($totale_utile_prev)), 2); ?>
                    </th>
                </tr>

            </tbody>

            <tfoot>
                <tr>
                    <th></th>
                    <th></th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '<th>'.strtoupper($mesi[ $mese->format('n') ]).'</th>';

                        $mese->addMonth()->format('n');
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right">
                        <?php echo strtoupper(tr('Totale')); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<hr>

<!-- ANDAMENTO FINANZIARIO -->
<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-exchange"></i> <?php echo tr('Andamento finanziario'); ?></h3>


        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body collapse in" style="overflow-y:auto;">
        <canvas height="100" id="andamento_finanziario" ></canvas>
        <hr>
        <table class="table table-condensed table-bordered" style="table-layout:fixed;">
            <thead>
                <tr>
                    <th width="24"></th>
                    <th width="130"></th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '<th width="100">'.strtoupper($mesi[ $mese->format('n') ]).'</th>';

                        $mese->addMonth()->format('n');
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th width="110" class="text-right">
                        <?php echo strtoupper(tr('Totale')); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <!-- ENTRATE REALI -->
                <tr>
                    <td rowspan="3" class="text-center">
                        <i class="fa fa-chevron-circle-up text-success"></i>
                    </td>

                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Incassi ricevuti e consolidati'); ?>"><?php echo strtoupper(tr('Entrate')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_ricavi = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = entrate_reali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">
                                <a class="'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=entrate_reali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_ricavi[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                    <?php echo moneyFormat(sum(array_values($totale_ricavi)), 2); ?>
                    </th>
                </tr>

                <!-- ENTRATE PREVISIONALI -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Incassi previsti con aggiunta dell\'iva e delle eventuali rateizzazioni in base al previsionale o sorgenti dati esterne'); ?>"><?php echo strtoupper(tr('Prev. entrate')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_ricavi_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = entrate_previsionali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">
                                <a class="'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=entrate_previsionali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : '').'">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_ricavi_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                    <?php echo moneyFormat(sum(array_values($totale_ricavi_prev)), 2); ?>
                    </th>
                </tr>

                <!-- ENTRATE TOTALI -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma degli incassi reali e degli incassi previsionali'); ?>"><?php echo strtoupper(tr('Entrate totali')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right success text-success">
                            <strong>'.moneyFormat(sum($totale_ricavi[$anno.'-'.$mese->format('n')], $totale_ricavi_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success">
                        <?php echo moneyFormat(sum(array_values($totale_ricavi), array_values($totale_ricavi_prev)), 2); ?>
                    </th>
                </tr>



                <!-- USCITE REALI -->
                <tr>
                    <td rowspan="3" class="text-center">
                        <i class="fa fa-chevron-circle-down text-danger"></i>
                    </td>

                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Pagamenti effettuati e consolidati'); ?>"><?php echo strtoupper(tr('Uscite')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_costi = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = uscite_reali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right danger text-danger">
                                <a class="text-danger" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=uscite_reali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right danger text-danger">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_costi[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                    <?php echo moneyFormat(sum(array_values($totale_costi)), 2); ?>
                    </th>
                </tr>

                <!-- USCITE PREVISIONALI -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Pagamenti previsti con aggiunta dell\'iva e delle eventuali rateizzazioni in base al previsionale o sorgenti dati esterne'); ?>"><?php echo strtoupper(tr('Prev. uscite')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_costi_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = uscite_previsionali($mese->format('n'), $anno);

                        if (!empty($movimenti[0]['importo'])) {
                            echo '
                            <td class="text-right danger text-danger">
                                <a class="text-danger" data-toggle="modal" data-title="'.tr('Dettagli').'" data-href="'.$structure->fileurl('dettagli.php').'?periodo='.$anno.'-'.$mese->format('n').'&op=uscite_previsionali">'.moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        } else {
                            echo '
                            <td class="text-right danger text-danger">'
                                .moneyFormat($movimenti[0]['importo'], 2).
                            '</td>';
                        }

                        $totale_costi_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                        <?php echo moneyFormat(sum(array_values($totale_costi_prev)), 2); ?>
                    </th>
                </tr>

                <!-- USCITE TOTALI -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma dei pagamenti effettuati e dei pagamenti previsti'); ?>"><?php echo strtoupper(tr('Uscite totali')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right danger text-danger">
                            <strong>'.moneyFormat(sum($totale_costi[$anno.'-'.$mese->format('n')], $totale_costi_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right danger text-danger">
                        <?php echo moneyFormat(sum(array_values($totale_costi), array_values($totale_costi_prev)), 2); ?>
                    </th>
                </tr>


                <!-- SALDO REALE -->
                <tr>
                    <td rowspan="4" class="text-center">
                        <i class="fa fa-line-chart text-primary"></i>
                    </td>

                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Saldo del periodo in base ad incassi e pagamenti'); ?>"><?php echo strtoupper(tr('Saldo')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_utile = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = saldo_reale($mese->format('n'), $anno);

                        echo '<td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : ' danger text-danger').'">'
                            .moneyFormat($movimenti[0]['importo'], 2).
                        '</td>';

                        $totale_utile[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right<?php echo (sum(array_values($totale_utile)) >= 0) ? ' success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile)), 2); ?>
                    </th>
                </tr>

                <!-- SALDO PREVISIONALE -->
                <tr>
                    <td class="text-right">
                        <span class="tip" title="<?php echo tr('Saldo previsionale del periodo in base ad incassi ricevuti e pagamenti effettuati'); ?>"><?php echo strtoupper(tr('Prev. saldo')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </td>

                    <?php
                    $totale_utile_prev = [];
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        $movimenti = saldo_previsionale($mese->format('n'), $anno);

                        echo '<td class="text-right'.(($movimenti[0]['importo'] >= 0) ? ' success text-success' : ' danger text-danger').'">'
                            .moneyFormat($movimenti[0]['importo'], 2).
                        '</td>';

                        $totale_utile_prev[$anno.'-'.$mese->format('n')] = $movimenti[0]['importo'];
                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right<?php echo (sum(array_values($totale_utile_prev)) >= 0) ? ' success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile_prev)), 2); ?>
                    </th>
                </tr>

                <!-- SALDO TOTALE -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Somma del periodo del saldo reale e previsionale'); ?>"><?php echo strtoupper(tr('Saldo totale')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');
                    $andamento_saldo = [];

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right'.((sum($totale_utile[$anno.'-'.$mese->format('n')], $totale_utile_prev[$anno.'-'.$mese->format('n')]) >= 0) ? ' success text-success' : ' danger text-danger').'">
                            <strong>'.moneyFormat(sum($totale_utile[$anno.'-'.$mese->format('n')], $totale_utile_prev[$anno.'-'.$mese->format('n')]), 2).'</strong>'.
                        '</td>';

                        // Conteggio andamento saldo cumulativo
                        $andamento_prev = (isset($andamento_saldo[$anno.'-'.($m-1)])) ? $andamento_saldo[$anno.'-'.($m-1)] : 0;
                        $andamento_saldo[$anno.'-'.$mese->format('n')] = sum($totale_utile[$anno.'-'.$mese->format('n')], $totale_utile_prev[$anno.'-'.$mese->format('n')]) + $andamento_prev;

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right <?php echo (sum(array_values($totale_utile), array_values($totale_utile_prev)) >= 0) ? ' success text-success' : ' danger text-danger'; ?>">
                        <?php echo moneyFormat(sum(array_values($totale_utile), array_values($totale_utile_prev)), 2); ?>
                    </th>
                </tr>


                <!-- LIQUIDITA' -->
                <tr>
                    <th class="text-right">
                        <span class="tip" title="<?php echo tr('Liquidità disponibile considerati i saldi di ciascun mese'); ?>"><?php echo strtoupper(tr('Liquidita\'')); ?> <i class="fa fa-question-circle-o"></i></span>
                    </th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '
                        <td class="text-right'.($andamento_saldo[$anno.'-'.$mese->format('n')] >= 0 ? ' success text-success' : ' danger text-danger').'">
                            <strong>'.moneyFormat($andamento_saldo[$anno.'-'.$mese->format('n')], 2).'</strong>'.
                        '</td>';

                        $mese->addMonth();
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right success text-success"></th>
                </tr>

            </tbody>

            <tfoot>
                <tr>
                    <th></th>
                    <th></th>

                    <?php
                    $mese = $start->copy();
                    $anno = $mese->format('Y');

                    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
                        echo '<th>'.strtoupper($mesi[ $mese->format('n') ]).'</th>';

                        $mese->addMonth()->format('n');
                        $anno = $mese->format('Y');
                    }
                    ?>

                    <th class="text-right">
                        <?php echo strtoupper(tr('Totale')); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
/**
 * Lettura minimo e massimo per arrotondamenti grafico
 */
$ricavi_reali = ricavi_reali_chart($start, $end);
$ricavi_totali = ricavi_totali_chart($start, $end);

$costi_reali = costi_reali_chart($start, $end);
$costi_totali = costi_totali_chart($start, $end);

$utile_reale = utile_reale_chart($start, $end);
$utile_totale = utile_totale_chart($start, $end);

$economico_min = min($costi_totali);
$economico_max = max($ricavi_totali);
$economico_cifre_min = round($economico_min, -(strlen(ceil($economico_min))-2))*1.50;
$economico_cifre_max = round($economico_max, -(strlen(ceil($economico_max))-2))*1.50;

if ($economico_cifre_min == $economico_cifre_max || $economico_cifre_min > 0) {
    $economico_cifre_min = 0;
}


// Conteggio finanziario
$entrate_reali = entrate_reali_chart($start, $end);
$entrate_totali = entrate_totali_chart($start, $end);

$uscite_reali = uscite_reali_chart($start, $end);
$uscite_totali = uscite_totali_chart($start, $end);

$saldo_totale = saldo_totale_chart($start, $end);
$saldo_cumulativo_totale = [];

foreach ($saldo_totale as $idx => $totale) {
    $totale_prev = (isset($saldo_cumulativo_totale[$idx-1])) ? $saldo_cumulativo_totale[$idx-1] : 0;
    $saldo_cumulativo_totale[$idx] = $totale + $totale_prev;
}

$saldo_reale = saldo_reale_chart($start, $end);
$saldo_cumulativo_reale = [];

foreach ($saldo_reale as $idx => $totale) {
    $totale_prev = (isset($saldo_cumulativo_reale[$idx-1])) ? $saldo_cumulativo_reale[$idx-1] : 0;
    $saldo_cumulativo_reale[$idx] = $totale + $totale_prev;
}


$finanziario_min = min(array_merge($uscite_totali, $saldo_cumulativo_totale));
$finanziario_max = max(array_merge($entrate_totali, $saldo_cumulativo_totale));
$finanziario_cifre_min = round($finanziario_min, -(strlen(ceil($finanziario_min))-2))*0.50;
$finanziario_cifre_max = round($finanziario_max, -(strlen(ceil($finanziario_max))-2))*1.50;

if ($finanziario_cifre_min == $finanziario_cifre_max || $finanziario_cifre_min > 0) {
    $finanziario_cifre_min = 0;
}

$months = get_months($start, $end);
?>


<!-- SCRIPT PER I GRAFICI -->
<script>

    start = moment("<?php echo $_SESSION['period_start'];?>");
    end = moment("<?php echo $_SESSION['period_end'];?>");

    var ricavi_reali = <?php echo json_encode($ricavi_reali, 1); ?>;
    var ricavi_totali = <?php echo json_encode($ricavi_totali, 1); ?>;
    
    var costi_reali = <?php echo json_encode($costi_reali, 1); ?>;
    var costi_totali = <?php echo json_encode($costi_totali, 1); ?>;
    
    var utile_reale = <?php echo json_encode($utile_reale, 1); ?>;
    var utile_totale = <?php echo json_encode($utile_totale, 1); ?>;

    
    // Costruzione grafico economico
    var options_economico = {
        type: 'line',
        data: {
            labels: <?php echo $months; ?>,
            datasets: [
                {
                    label: '<?php echo tr('Ricavi reali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(80,195,80,0.5)',
                    borderColor: 'rgba(80,195,80,1)',
                    data: ricavi_reali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Ricavi totali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(139,218,139,0.5)',
                    borderColor: 'rgba(139,218,139,1)',
                    data: ricavi_totali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Costi reali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(255,76,58,0.5)',
                    borderColor: 'rgba(255,76,58,1)',
                    data: costi_reali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Costi totali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(255,153,58,0.5)',
                    borderColor: 'rgba(255,153,58,1)',
                    data: costi_totali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Utile reale'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(58,154,255,0.5)',
                    borderColor: 'rgba(58,154,255,1)',
                    data: utile_reale,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Utile totale'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(124,179,238,0.5)',
                    borderColor: 'rgba(124,179,238,1)',
                    data: utile_totale,
                }
            ]
        },
        options: {
            responsive: true,
            elements: {
				line: {
					tension: 0
				}
			},
            title: {
                display: false,
                text: ''
            },
            legend: {
                display: true,
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItems, data) { 
                        return tooltipItems.yLabel.toLocale() + ' €';
                    }
                }
            },
            annotation: {
                annotations: [{
                    type: 'line',
                    mode: 'horizontal',
                    scaleID: 'y-axis-0',
                    value: 0,
                    borderColor: 'rgba(255, 0, 0, 0.5)',
                    borderWidth: 2,
                    label: {
                        enabled: false,
                    }
                }]
            },
            hover: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                xAxes: [{
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: 'Mese'
                    }
                }],
                yAxes: [{
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: 'Andamento'
                    },
                    ticks: {
                        min: <?php echo $economico_cifre_min; ?>,
                        max: <?php echo $economico_cifre_max; ?>,
                        scaleBeginAtZero: true,
                        stepSize: 1000,
                        callback: function(value, index, values) {
                            return value.toLocale();
                        }
                    }
                }]
            }
        }
    }


    var saldo_cumulativo_totale = <?php echo json_encode($saldo_cumulativo_totale); ?>;
    var saldo_cumulativo_reale = <?php echo json_encode($saldo_cumulativo_reale); ?>;

    var entrate_reali = <?php echo json_encode($entrate_reali); ?>;
    var entrate_totali = <?php echo json_encode($entrate_totali); ?>;

    var uscite_reali = <?php echo json_encode($uscite_reali); ?>;
    var uscite_totali = <?php echo json_encode($uscite_totali); ?>;

    var finanziario = {
        type: 'line',
        data: {
            labels: <?php echo $months; ?>,
            datasets: [
                {
                    label: '<?php echo tr('Entrate reali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(80,195,80,0.5)',
                    borderColor: 'rgba(80,195,80,1)',
                    data: entrate_reali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Entrate totali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(139,218,139,0.5)',
                    borderColor: 'rgba(139,218,139,1)',
                    data: entrate_totali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Uscite reali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(255,76,58,0.5)',
                    borderColor: 'rgba(255,76,58,1)',
                    data: uscite_reali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Uscite totali'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(255,153,58,0.5)',
                    borderColor: 'rgba(255,153,58,1)',
                    data: uscite_totali,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Saldo reale'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(58,154,255,0.5)',
                    borderColor: 'rgba(58,154,255,1)',
                    data: saldo_cumulativo_reale,
                    hidden: true
                },
                {
                    label: '<?php echo tr('Saldo cumulativo'); ?>',
                    fill: true,
                    backgroundColor: 'rgba(124,179,238,0.5)',
                    borderColor: 'rgba(124,179,238,1)',
                    data: saldo_cumulativo_totale,
                }
            ]
        },
        options: {
            responsive: true,
            elements: {
				line: {
					tension: 0
				}
			},
            title: {
                display: false,
                text: ''
            },
            legend: {
                display: true,
            },
            tooltips: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    label: function(tooltipItems, data) { 
                        return tooltipItems.yLabel.toLocale() + ' €';
                    }
                }
            },
            annotation: {
                annotations: [{
                    type: 'line',
                    mode: 'horizontal',
                    scaleID: 'y-axis-0',
                    value: 0,
                    borderColor: 'rgba(255, 0, 0, 0.5)',
                    borderWidth: 2,
                    label: {
                        enabled: false,
                    }
                }]
            },
            hover: {
                mode: 'nearest',
                intersect: false
            },
            scales: {
                xAxes: [{
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: 'Mese'
                    }
                }],
                yAxes: [{
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: 'Saldo cumulativo'
                    },
                    ticks: {
                        suggestedMin: <?php echo $finanziario_cifre_min; ?>,
                        suggestedMax: <?php echo $finanziario_cifre_max; ?>,
                        scaleBeginAtZero: true,
                        stepSize: 1000,
                        callback: function(value, index, values) {
                            return value.toLocale();
                        }
                    }
                }]
            }
        }
    };

    window.onload = function() {
        // Grafico economico
        var ctx_economico = document.getElementById('andamento_economico').getContext('2d');
        
        new Chart(ctx_economico, options_economico);

        // Grafico finanziario
        var ctx_finanziario = document.getElementById('andamento_finanziario').getContext('2d');
        window.myLine = new Chart(ctx_finanziario, finanziario);
    }; 

    var sub_ricavi = $('.ricavi.hide').length;

    function show_ricavi() {
        $('.ricavi').toggleClass('hide');
        $('.plus-ricavi').toggleClass('fa fa-plus').toggleClass('fa fa-minus');

        // Cambio il rowspan se il menù dei ricavi è nascosto
        if($('.ricavi.hide').length == sub_ricavi){
            $('#td-ricavi').attr('rowspan', 3);
        } else{
            $('#td-ricavi').attr('rowspan', 3 + sub_ricavi);
        }
    }

    var sub_costi = $('.costi.hide').length;

    function show_costi() {
        $('.costi').toggleClass('hide');
        $('.plus-costi').toggleClass('fa fa-plus').toggleClass('fa fa-minus');

        // Cambio il rowspan se il menù dei costi è nascosto
        if($('.costi.hide').length == sub_costi){
            $('#td-costi').attr('rowspan', 3);
        } else{
            $('#td-costi').attr('rowspan', 3 + sub_costi);
        }
    }

</script>


<style>
th, td{
    font-size: 80%;
}

.table > tbody > tr > td {
     vertical-align: middle;
}
</style>