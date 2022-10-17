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

$id_module = get('id_module');
$id_record = get('id_record');
$new_state = get('new_state');

$query = 'SELECT a.id, sa.codice
        FROM at_attivita a
        JOIN at_stati_attivita sa ON a.idstatointervento = sa.idstatointervento
        WHERE a.parent = ' .prepare($id_record) . '
        AND a.idstatointervento <> 7';

$subactivities = $dbo->fetchArray($query);

if ($subactivities != null) { //non è possibile settare l'attività come terminata
    $query = 'SELECT a.idstatointervento, sa.descrizione
            FROM at_attivita a
            JOIN at_stati_attivita sa ON a.idstatointervento = sa.idstatointervento
            WHERE a.id = ' .prepare($id_record);

    $activity = $dbo->fetchArray($query);

    $ret = [
        "id" => $activity[0]['idstatointervento'],
        "descrizione" => $activity[0]['descrizione'],
    ];

    echo json_encode($ret);
} else {
    echo "";
}
