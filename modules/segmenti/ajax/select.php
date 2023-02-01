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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'segmenti':
        $user = Auth::user();
        $id_module = $superselect['id_module'];
        $is_fiscale = $superselect['is_fiscale'];
        $is_sezionale = $superselect['is_sezionale'];

        if (isset($id_module)) {
            $query = 'SELECT `id`, `name` AS descrizione FROM zz_segments INNER JOIN `zz_group_segment` ON `zz_segments`.`id` = `zz_group_segment`.`id_segment` |where| ORDER BY `name` ASC';

            $where[] = 'zz_segments.id_module = '.prepare($id_module);
            $where[] = 'zz_group_segment.id_gruppo = '.prepare($user->idgruppo);

            if ($is_fiscale != null) {
                $where[] = 'zz_segments.is_fiscale = '.prepare($is_fiscale);
            }

            if ($is_sezionale != null) {
                $where[] = 'zz_segments.is_sezionale = '.prepare($is_sezionale);
            }

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            if (!empty($search)) {
                $search_fields[] = 'zz_segments.name LIKE '.prepare('%'.$search.'%');
            }
        }

        break;
}
