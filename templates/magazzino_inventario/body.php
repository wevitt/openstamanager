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

use Util\Query;

include_once __DIR__.'/../../core.php';

$id_module = Modules::get('Articoli')['id'];

// Valori di ricerca
$where['servizio'] = '0';

foreach ($_SESSION['module_'.$id_module] as $name => $value) {
    if (preg_match('/^search_(.+?)$/', $name, $m)) {
        $where[$m[1]] = $value;
    }
}

$period_end = $_SESSION['period_end'];

$structure = Modules::get($id_module);

// RISULTATI VISIBILI
Util\Query::setSegments(false);
$query = Query::getQuery($structure, $where, 0, []);

$query = Modules::replaceAdditionals($id_module, $query);

// Modifiche alla query principale
$query = preg_replace('/FROM `mg_articoli`/', ' FROM mg_articoli LEFT JOIN (SELECT idarticolo, SUM(qta) AS qta_totale FROM mg_movimenti WHERE data <='.prepare($period_end).' GROUP BY idarticolo) movimenti ON movimenti.idarticolo=mg_articoli.id ', $query);

$query = preg_replace('/^SELECT /', 'SELECT mg_articoli.prezzo_vendita,', $query);
$query = preg_replace('/^SELECT /', 'SELECT mg_articoli.um,', $query);
$query = preg_replace('/^SELECT /', 'SELECT movimenti.qta_totale,', $query);

if (post('acquisto') == 'standard') {
    $query = preg_replace('/^SELECT /', 'SELECT mg_articoli.prezzo_acquisto AS acquisto,', $query);
    $text = "al prezzo presente nella scheda articolo";
} elseif(post('acquisto') == 'first') {  
    $query = preg_replace('/^SELECT /', 'SELECT (SELECT (prezzo_unitario-sconto_unitario) AS acquisto FROM co_righe_documenti LEFT JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_documenti.idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita") AND idarticolo=mg_articoli.id ORDER BY co_righe_documenti.id  ASC LIMIT 0,1) AS acquisto,', $query);
    $text = "al primo articolo acquistato";
} elseif(post('acquisto') == 'last') {
    $query = preg_replace('/^SELECT /', 'SELECT (SELECT (prezzo_unitario-sconto_unitario) AS acquisto FROM co_righe_documenti LEFT JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_documenti.idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita") AND idarticolo=mg_articoli.id ORDER BY co_righe_documenti.id  DESC LIMIT 0,1) AS acquisto,', $query);
    $text = "all'ultimo articolo acquistato";
} else {
    $query = preg_replace('/^SELECT /', 'SELECT (SELECT (SUM((prezzo_unitario-sconto_unitario)*qta)/SUM(qta)) AS acquisto FROM co_righe_documenti LEFT JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_documenti.idtipodocumento IN(SELECT id FROM co_tipidocumento WHERE dir="uscita") AND idarticolo=mg_articoli.id) AS acquisto,', $query);
    $text = "alla media ponderata dell'articolo";
}

if (post('tipo') == 'nozero') {
    $query = str_replace('2=2', '2=2 AND movimenti.qta_totale > 0', $query);
}

$data = Query::executeAndCount($query);

echo '
<h3>'.tr('Inventario al _DATE_', [
    '_DATE_' => Translator::dateToLocale($period_end),
], ['upper' => true]).'</h3>

<p style="color:#aaa; font-size:10px;" class="text-right">
    '.tr("Prezzo di acquisto calcolato in base _TEXT_",
        [
            "_TEXT_" => $text,
        ]).'
</p>

<table class="table table-bordered">
    <thead>
        <tr>
            <th class="text-center" width="150">'.tr('Codice', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Categoria', [], ['upper' => true]).'</th>
            <th class="text-center">'.tr('Descrizione', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Prezzo di vendita', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Q.tà', [], ['upper' => true]).'</th>
            <th class="text-center" width="70">'.tr('Prezzo di acquisto', [], ['upper' => true]).'</th>
            <th class="text-center" width="90">'.tr('Valore totale', [], ['upper' => true]).'</th>
        </tr>
    </thead>

    <tbody>';

$totale_qta = 0;
$totali = [];
foreach ($data['results'] as $r) {
    $valore_magazzino = $r['acquisto'] * $r['qta_totale'];

    echo '
        <tr>
            <td>'.$r['Codice'].'</td>
            <td>'.$r['Categoria'].'</td>
            <td>'.$r['Descrizione'].'</td>
            <td class="text-right">'.moneyFormat($r['prezzo_vendita']).'</td>
            <td class="text-right">'.Translator::numberToLocale($r['qta_totale']).' '.$r['um'].'</td>
            <td class="text-right">'.moneyFormat($r['acquisto']).'</td>
            <td class="text-right">'.moneyFormat($valore_magazzino).'</td>
        </tr>';

    $totale_qta += $r['qta_totale'];
    $totali[] = $valore_magazzino;
}

// Totali
$totale_acquisto = sum($totali);
echo '
    </tbody>

    <tr>
        <td colspan="3" class="text-right border-top"><b>'.tr('Totale', [], ['upper' => true]).':</b></td>
        <td class="border-top"></td>
        <td class="text-right border-top"><b>'.Translator::numberToLocale($totale_qta).'</b></td>
        <td class="border-top"></td>
        <td class="text-right border-top"><b>'.moneyFormat($totale_acquisto).'</b></td>
    </tr>
</table>';
