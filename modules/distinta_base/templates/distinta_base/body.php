<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Articoli\Articolo;

$obj = Articolo::find($id_record);

$righe = $obj->componenti;

// Intestazione tabella per righe
echo "
<table>
    <thead>
        <tr class='border-full'>
            <th style='width:20%'>".tr('Codice', [], ['upper' => true])."</th>
            <th>".tr('Descrizione')."</th>
            <th class='text-center' style='width:10%'>".tr('Q.tà')."</th>
            <th class='text-center' style='width:15%'>".tr('Stato')."</th>
        </tr>
    </thead>

    <tbody>";

foreach ($righe as $riga) {
    echo '
        <tr>';

        // Codice articolo
        echo '
            <td>
                '.$riga->codice.'
            </td>

            <td>
                '.nl2br($riga->descrizione).'
            </td>';

        $qta = $riga->pivot->qta;

        echo '
            <td class="text-center">
                '.Translator::numberToLocale(abs($qta), 'qta').'
            </td>';

    echo '
            <td class="text-center">
                <img src="'.base_path().'/templates/distinta_base/checkbox.png" class="img-thumbnail" style="width:4mm; border:0;">
            </td>
        </tr>';
}

echo '
    </tbody>
</table>';