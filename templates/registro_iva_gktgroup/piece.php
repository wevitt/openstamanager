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

if ($id_sezionale == -1) {
    echo '
    <tr>
        <td colspan="10"><b>'.$record[0]['sezionale'].'</b></td>
    </tr>';
}

usort($record, function ($a, $b) {
    return $a['numero'] <=> $b['numero'];
});
foreach ($record as $r) {
    if ($numero != $r['numero']) {
        $different = 1;
    }

    echo '
        <tr>';

        echo '
            <td>'.($different ? $r['numero'] : '').'</td>
            <td>'.($different ? Translator::datetoLocale($r['data_registrazione']) : '').'</td>
            <td>'.($different ? Translator::datetoLocale($r['data']) : '').'</td>
            <td>'.($different ? $r['codice_tipo_documento_fe'] : '').'</td>
            <td>'.($different ? $r['codice_anagrafica'].' '.safe_truncate(mb_strtoupper(html_entity_decode($r['ragione_sociale']), 'UTF-8'), 50) : '').'</td>
            <td class="text-right">'.moneyFormat($r['totale']).'</td>';

        echo '
            <td class="text-right">'.moneyFormat($r['subtotale']).'</td>
            <td class="text-left">'.Translator::numberToLocale($r['percentuale'], 0).'</td>
            <td class="text-left">';
    echo $r['descrizione'];
    echo ($record['split_payment'] != 0) ? '<br>' . tr('Split payment') : '';
    echo '</td>
            <td class="text-right">'.moneyFormat($r['iva'], 3).'</td>
            </tr>';

        $iva[$r['descrizione']][] = $r['iva'];
        $totale[$r['descrizione']][] = $r['subtotale'];
    if ($record['split_payment'] != 0) {
        $split_payment_iva[$record['descrizione'] . ' ' . tr('Split payment')][] = $record['iva'];
        $split_payment_totale[$record['descrizione'] . ' ' . tr('Split payment')][] = $record['subtotale'];
    }

        $numero = $r['numero'];
        $data_registrazione = $r['data_registrazione'];
        $numero_esterno = $r['numero'];
        $data = $r['data'];
        $codice_fe = $r['numero'];
        $codice_anagrafica = $r['numero'];

        $different = 0;
}
