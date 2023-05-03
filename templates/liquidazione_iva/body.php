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

$totale_iva_vendite = sum(array_column($iva_vendite, 'iva'));
$totale_subtotale_vendite = sum(array_column($iva_vendite, 'subtotale'));
$totale_iva_acquisti = sum(array_column($iva_acquisti, 'iva'));
$totale_subtotale_acquisti = sum(array_column($iva_acquisti, 'subtotale'));

$totale_iva_esigibile = sum(array_column($iva_vendite_esigibile, 'iva'));
$totale_iva_nonesigibile = sum(array_column($iva_vendite_nonesigibile, 'iva'));
$subtotale_iva_esigibile = sum(array_column($iva_vendite_esigibile, 'subtotale'));
$subtotale_iva_nonesigibile = sum(array_column($iva_vendite_nonesigibile, 'subtotale'));

$totale_iva_detraibile = sum(array_column($iva_acquisti_detraibile, 'iva'));
$totale_iva_nondetraibile = sum(array_column($iva_acquisti_nondetraibile, 'iva'));
$subtotale_iva_detraibile = sum(array_column($iva_acquisti_detraibile, 'subtotale'));
$subtotale_iva_nondetraibile = sum(array_column($iva_acquisti_nondetraibile, 'subtotale'));

$totale_iva_vendite_anno_precedente = sum(array_column($iva_vendite_anno_precedente, 'iva'));
$totale_iva_acquisti_anno_precedente = sum(array_column($iva_acquisti_anno_precedente, 'iva'));
$totale_iva_anno_precedente = $totale_iva_vendite_anno_precedente - $totale_iva_acquisti_anno_precedente;

$totale_iva_vendite_periodo_precedente = sum(array_column($iva_vendite_periodo_precedente, 'iva'));
$totale_iva_acquisti_periodo_precedente = sum(array_column($iva_acquisti_periodo_precedente, 'iva'));
$totale_iva_periodo_precedente = $totale_iva_vendite_periodo_precedente - $totale_iva_acquisti_periodo_precedente;

$totale_iva = $totale_iva_esigibile - $totale_iva_detraibile;

if ($periodo['valore'] == 'Trimestrale' && $totale_iva > 25.82) {
    if ($totale_iva_periodo_precedente < 25.82 && $totale_iva_periodo_precedente > 0) {
        $totale_iva += $totale_iva_periodo_precedente;
    }
    $maggiorazione = $totale_iva * 0.01;
    $totale_iva_maggiorata = $totale_iva + $maggiorazione;
}

echo '
<h5 class="text-center">VENDITE</h5>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th width="15%">Aliquota</th>
        <th width="15%">Natura IVA</th>
        <th width="30%">Descrizione</th>
        <th class="text-right" width="20%">Imponibile</th>
        <th class="text-right" width="20%">Imposta</th>
    </tr>
</thead> 
<tbody>
    <tr>
        <th class="text-center" colspan="5">IVA ESIGIBILE DEL PERIODO</th>
    </tr>';

foreach ($iva_vendite_esigibile as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
        <td colspan="2"></td>
        <td>TOTALI</td>
        <td class=text-right>'.moneyFormat($subtotale_iva_esigibile, 2).'</td>
        <td class=text-right>'.moneyFormat($totale_iva_esigibile, 2).'</td>
    </tr>
    
<tr>
    <th class="text-center" colspan="5">IVA NON ESIGIBILE DEL PERIODO</th>
</tr>';

foreach ($iva_vendite_nonesigibile as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_nonesigibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_nonesigibile, 2).'</td>
</tr>

<tr>
    <th class="text-center" colspan="5">RIEPILOGO GENERALE IVA VENDITE</th>
</tr>';
foreach ($iva_vendite as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <th colspan="2"></th>
    <th>TOTALE</th>
    <th class=text-right>'.moneyFormat($totale_subtotale_vendite, 2).'</th>
    <th class=text-right>'.moneyFormat($totale_iva_vendite, 2).'</th>
</tr>
</tbody>
</table>


<h5 class="text-center">ACQUISTI</h5>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th width="15%">Aliquota</th>
        <th width="15%">Natura IVA</th>
        <th width="30%">Descrizione</th>
        <th class="text-right" width="20%">Imponibile</th>
        <th class="text-right" width="20%">Imposta</th>
    </tr>
</thead> 
<tbody>
    <tr>
        <th class="text-center" colspan="5">IVA DETRAIBILE DEL PERIODO</th>
    </tr>';

foreach ($iva_acquisti_detraibile as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_detraibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_detraibile, 2).'</td>
</tr>


<tr>
    <th class="text-center" colspan="5">IVA NON DETRAIBILE DEL PERIODO</th>
</tr>';

foreach ($iva_acquisti_nondetraibile as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}
echo '
<tr>
    <td colspan="2"></td>
    <td>TOTALI</td>
    <td class=text-right>'.moneyFormat($subtotale_iva_nondetraibile, 2).'</td>
    <td class=text-right>'.moneyFormat($totale_iva_nondetraibile, 2).'</td>
</tr>


<tr>
    <th class="text-center" colspan="5">RIEPILOGO GENERALE IVA ACQUISTI</th>
</tr>';
foreach ($iva_acquisti as $record) {
    echo '
    <tr>
        <td>'.round($record['aliquota']).'%</td>
        <td>'.$record['cod_iva'].'</td>
        <td>'.$record['descrizione'].'</td>
        <td class=text-right>'.moneyFormat($record['subtotale'], 2).'</td>
        <td class=text-right>'.moneyFormat($record['iva'], 2).'</td>
    </tr>';
}

echo '
<tr>
    <th colspan="2"></th>
    <th>TOTALE</th>
    <th class=text-right>'.moneyFormat($totale_subtotale_acquisti, 2).'</th>
    <th class=text-right>'.moneyFormat($totale_iva_acquisti, 2).'</th>
</tr>
</tbody>
</table>

<br>
<br>
<table class="table table-condensed table-striped table-bordered">
<thead>
    <tr>
        <th class="text-center" colspan="2">PROSPETTO RIEPILOGATIVO DI LIQUIDAZIONE I.V.A.</th>
    <tr>
        <th width="70%">DESCRIZIONE</th>
        <th class="text-right" width="30%">IMPORTO</th>
    </tr>
</thead>
<tbody>
    <tr>';
        if ($totale_iva_anno_precedente >= 0) {
            echo ' <td>DEBITO ANNO PRECEDENTE</td>';
        } else {
            echo ' <td>CREDITO ANNO PRECEDENTE</td>';
        }
        echo '<td class=text-right>'.moneyFormat(abs($totale_iva_anno_precedente), 2).'</td>
    </tr>
    <tr>';
        if ($totale_iva_periodo_precedente >= 0) {
            echo ' <td>DEBITO PERIODO PRECEDENTE</td>';
        } else {
            echo ' <td>CREDITO PERIODO PRECEDENTE</td>';
        }
        echo ' <td class=text-right>'.moneyFormat(abs($totale_iva_periodo_precedente), 2).'</td>
    </tr>    
    <tr>
        <td>TOTALE IVA SU VENDITE ESIGIBILE</td>
        <td class=text-right>'.moneyFormat($totale_iva_esigibile, 2).'</td>
    </tr>    
    <tr>
        <td>TOTALE IVA OGGETTIVAMENTE NON A DEBITO SU VENDITE</td>
        <td class=text-right>'.moneyFormat($totale_iva_nonesigibile, 2).'</td>
    </tr>
    <tr>
        <td>TOTALE IVA SU ACQUISTI DETRAIBILI</td>
        <td class=text-right>'.moneyFormat($totale_iva_detraibile, 2).'</td>
    </tr>
    <tr>
        <td>TOTALE IVA OGGETTIVAMENTE INDETRAIBILI SU ACQUISTI</td>
        <td class=text-right>'.moneyFormat($totale_iva_nondetraibile, 2).'</td>
    </tr>
    <tr>
        <td>VARIAZIONE DI IMPOSTA RELATIVE A PERIODI PRECEDENTI</td>';
        if ($totale_iva_periodo_precedente < 25.82 && $totale_iva_periodo_precedente > 0) {
            echo'
            <td class=text-right>'.moneyFormat(abs($totale_iva_periodo_precedente), 2).'</td>';
        } else {
            echo'
            <td class=text-right></td>';
        } 
    echo'
    </tr>
    <tr>
        <td>DI CUI INTERESSI PER RAVVEDIMENTO</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>DI CUI INTERESSI PER MAGGIORAZIONE TRIMESTRALI</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>CREDITO IVA COMPENSABILE</td>
        <td class=text-right></td>
    </tr>
    <tr>';
        if ($totale_iva >= 0) {
            echo ' <td>IVA A DEBITO</td>';
        } else {
            echo ' <td>IVA A CREDITO</td>';
        }
        echo ' <td class=text-right>'.moneyFormat(abs($totale_iva), 2).'</td>
    </tr>
    <tr>
        <td>CREDITO SPECIALE DI IMPOSTA</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>MAGGIORAZIONE 1,00%</td>
        <td class=text-right>'.($periodo['valore'] == 'Trimestrale' && $totale_iva > 25.82 ? moneyFormat($maggiorazione, 2) : '').'</td>
    </tr>
    <tr>
        <td>IVA A DEBITO CON MAGGIORAZIONE</td>';
        if ($totale_iva > 25.82 && $periodo['valore'] == 'Trimestrale') {
            echo'
            <td class=text-right>'.moneyFormat($totale_iva_maggiorata, 2).'</td>';
        } else {
            echo'
            <td class=text-right></td>';
        } 
    echo'
    </tr>
    <tr>
        <td>IMPORTO DA VERSARE</td>';
        if ($totale_iva > 25.82) {
            if ($periodo['valore'] == 'Mensile') {
                echo'
                <td class=text-right>'.moneyFormat($totale_iva, 2).'</td>';
            } else {
                echo'
                <td class=text-right>'.moneyFormat($totale_iva_maggiorata, 2).'</td>';
            }
        } else {
            echo'
            <td class=text-right></td>';
        } 
    echo'
    </tr>
    <tr>
        <td>CREDITO INFRANNUALE DI IMPOSTA CHIESTO A RIMBORSO</td>
        <td class=text-right></td>
    </tr>
    <tr>
        <td>CREDITO INFRANNUALE DA UTILIZZARE IN COMPENSAZIONE</td>
        <td class=text-right></td>
    </tr>
</tbody>
</table>';
