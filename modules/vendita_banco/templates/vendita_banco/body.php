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

use Carbon\CarbonInterval;
use Modules\Anagrafiche\Anagrafica;
use Modules\Pagamenti\Pagamento;

include_once __DIR__.'/../../core.php';

$anagrafica = Anagrafica::find($documento['idanagrafica']);
$pagamento = Pagamento::find($documento['idpagamento']);
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Righe documento
$righe = $documento->getRighe();

$has_image = $righe->search(function ($item) {
    return !empty($item->articolo->immagine);
}) !== false;

$columns = 6;
if ($has_image) {
    ++$columns;
}

// Creazione righe fantasma
$autofill = new \Util\Autofill($columns);
$autofill->setRows(20, 20);

echo '
<div class="row">
    <div class="col-xs-6">
        <div class="text-center" style="height:5mm;">
            <b>'.tr('Vendita num. _NUM_ del _DATE_', [
                '_NUM_' => $documento['numero'],
                '_DATE_' => Translator::dateToLocale($documento['data']),
            ], ['upper' => true]).'</b>
        </div>

        <table class="table">
            <tr>
                <td colspan="2" style="height:10mm;padding-top:2mm;">
                    <p class="small-bold">'.tr('Pagamento', [], ['upper' => true]).'</p>
                    <p>'.$pagamento['descrizione'].'</p>
                </td>
            </tr>
        </table>
    </div>

	<div class="col-xs-6" style="margin-left: 10px">
        <table class="table" style="width:100%;margin-top:5mm;">
            <tr>
                <td colspan=2 class="border-full" style="height:16mm;">
                    <p class="small-bold">'.tr('Spett.le', [], ['upper' => true]).'</p>
                    <p>$c_ragionesociale$</p>
                    <p>$c_indirizzo$</p>
                    <p>$c_citta_full$</p>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Partita IVA', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_piva$</small>
                </td>
            </tr>

            <tr>
                <td class="border-bottom border-left">
                    <p class="small-bold">'.tr('Codice fiscale', [], ['upper' => true]).'</p>
                </td>
                <td class="border-right border-bottom text-right">
                    <small>$c_codicefiscale$</small>
                </td>
            </tr>';
        echo '
        </table>
    </div>
</div>';

// Intestazione tabella per righe
echo "
<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center'>#</th>";

if ($has_image) {
    echo "
            <th class='text-center' width='95' >Foto</th>";
}

echo "
            <th class='text-center' style='width:50%'>".tr('Descrizione', [], ['upper' => true])."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".tr('Prezzo unitario', [], ['upper' => true])."</th>
            <th class='text-center' style='width:15%'>".( $options['hide_total'] ? tr('Importo ivato', [], ['upper' => true ]) : tr( 'Importo', [], ['upper' => true]) )."</th>
            <th class='text-center' style='width:10%'>".tr('IVA', [], ['upper' => true]).' (%)</th>';
echo '
        </tr>
    </thead>

    <tbody>';

$num = 0;
foreach ($righe as $riga) {
    ++$num;
    $r = $riga->toArray();

    $autofill->count($r['descrizione']);

    echo '
        <tr>
            <td class="text-center" style="vertical-align: middle" width="25">
                '.$num.'
            </td>';

    if ($has_image) {
        if ($riga->isArticolo() && !empty($riga->articolo->image)) {
            echo '
            <td align="center">
                <img src="'.$riga->articolo->image.'" style="max-height: 60px; max-width:80px">
            </td>';

            $autofill->set(5);
        } else {
            echo '
            <td></td>';
        }
    }

    echo '
            <td style="vertical-align: middle">
                '.nl2br($r['descrizione']);

    if ($riga->isArticolo()) {
        // Codice articolo
        $text = tr('COD. _COD_', [
            '_COD_' => $riga->codice,
        ]);
        echo '
                <br><small>'.$text.'</small>';

        $autofill->count($text, true);
    }

    echo '
            </td>';

    if (!$riga->isDescrizione()) {
        echo '
            <td class="text-center" style="vertical-align: middle" >
                '.Translator::numberToLocale(abs($riga->qta), 'qta').' '.$r['um'].'
            </td>';

            // Prezzo unitario
            echo '
            <td class="text-right" style="vertical-align: middle">
				'.moneyFormat($prezzi_ivati ? $riga->prezzo_unitario_ivato : $riga->prezzo_unitario);

            if ($riga->sconto > 0) {
                $text = discountInfo($riga, false);

                echo '
                <br><small class="text-muted">'.$text.'</small>';

                $autofill->count($text, true);
            }

            echo '
            </td>';

            // Imponibile
            echo '
            <td class="text-right" style="vertical-align: middle" >
                '.( ($options['hide_total'] || $prezzi_ivati) ? moneyFormat($riga->totale) : moneyFormat($riga->totale_imponibile) ).'
            </td>';

            // Iva
            echo '
            <td class="text-center" style="vertical-align: middle">
                '.Translator::numberToLocale($riga->aliquota->percentuale, 0).'
            </td>';
    } else {
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    }

    echo '
        </tr>';

    $autofill->next();
}

echo '
        |autofill|
    </tbody>';

// Calcoli
$imponibile = $documento->imponibile;
$sconto = $documento->sconto;
$totale_imponibile = $documento->totale_imponibile;
$totale_iva = $documento->iva;
$totale = $documento->totale;

$show_sconto = $sconto > 0;

// TOTALE COSTI FINALI
// Totale imponibile
    echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($show_sconto ? $imponibile : $totale_imponibile, 2).'</b>
        </th>
    </tr>';

    // Eventuale sconto incondizionato
    if ($show_sconto) {
        echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Sconto', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($sconto, 2).'</b>
        </th>
    </tr>';

        // Totale imponibile
        echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale_imponibile, 2).'</b>
        </th>
    </tr>';
    }

    // IVA
    echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale IVA', [], ['upper' => true]).':</b>
        </td>

        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale_iva, 2).'</b>
        </th>
    </tr>';

    // TOTALE
    echo '
    <tr>
        <td colspan="'.($options['show_only_total'] ? 2 : 4).'" class="text-right border-top">
            <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
        </td>
        <th colspan="'.($options['show_only_total'] ? (($has_images) ? 2 : 1) : (($has_images) ? 3 : 2)).'" class="text-right">
            <b>'.moneyFormat($totale, 2).'</b>
        </th>
    </tr>
</table>';