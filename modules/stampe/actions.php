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

switch (post('op')) {
    case 'update':
        if (!empty(intval(post('predefined'))) && !empty(post('module'))) {
            $dbo->query('UPDATE zz_prints SET predefined = 0 WHERE zz_prints.id != '.prepare($id_record).' AND id_module = '.post('module'));
        }

        $print->title = post('title');
        $print->filename = post('filename');
        $print->options = post('options');
        //$print->id_module = post('module');
        //$print->enabled = post('enabled');
        $print->order = post('order');
        $print->predefined = intval(post('predefined'));

        $print->save();

        flash()->info(tr('Modifiche salvate correttamente'));

        break;
}
