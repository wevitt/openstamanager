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

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Righe documento
$righe = $documento->getRighe();

$columns = 7;

//Immagine solo per documenti di vendita
if ($documento->direzione == 'entrata') {
    $has_image = $righe->search(function ($item) {
        return !empty($item->articolo->immagine);
    }) !== false;

    if ($has_image) {
        ++$columns;
        $char_number = $options['pricing'] ? 26 : 63;
    }
}

if ($documento->direzione == 'uscita') {
    $columns += 2;
    $char_number = $options['pricing'] ? 26 : 63;
} else {
    $char_number = $options['pricing'] ? 45 : 82;
}
$columns = $options['pricing'] ? $columns : $columns - 3;

// Creazione righe fantasma
$autofill = new \Util\Autofill($columns, $char_number);
$autofill->setRows(30);

?>

<table class='table table-striped table-bordered' id='contents'>
    <thead>
        <tr>
            <th class='text-center' style='width:10%'><?php echo tr('Cod.', [], ['upper' => true]) ?></th>
            <th class='text-center'><?php echo tr('Descrizione', [], ['upper' => true]) ?></th>
            <th class='text-center' style='width:10%'><?php echo tr('Q.tà', [], ['upper' => true]) ?></th>
            <th class='text-center' style='width:15%'><?php echo tr('Q.tà da prelevare', [], ['upper' => true]) ?></th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($righe as $riga) { ?>
            <?php if (!$riga->is_spesa_trasporto && !$riga->is_spesa_incasso) { ?>
                <tr>
                    <?php
                        $r = $riga->toArray();
                        $autofill->count($r['descrizione']);
                        $qta = $riga->qta;
                        $qta_da_prelevare = $riga->qta - $riga->qta_evasa;
                        $um = $r['um'];
                    ?>
                    <td class="text-center" style="vertical-align: middle"><?php echo $riga->articolo->codice ?></td>
                    <td><?php echo nl2br($r['descrizione']) ?></td>
                    <td><?php echo Translator::numberToLocale(abs($qta), 'qta').' '.$um ?></td>
                    <td><?php echo Translator::numberToLocale(abs($qta_da_prelevare), 'qta').' '.$um ?></td>
                </tr>
            <?php } ?>
        <?php } ?>
    </tbody>
</table>

<?php
if (!empty($documento->condizioni_fornitura)) {
    echo '<pagebreak>'.$documento->condizioni_fornitura;
}

if (!empty($documento['note'])) {
    echo '
<br>
<p class="small-bold">'.tr('Note', [], ['upper' => true]).':</p>
<p>'.nl2br($documento['note']).'</p>';
}
?>
