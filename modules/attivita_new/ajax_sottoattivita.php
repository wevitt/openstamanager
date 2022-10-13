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

$id_activity = get('id_activity');
$j = get('i');

$modulo = base_path().'/editor.php';

$query_subactivities = 'SELECT a.id, sa.codice
                        FROM at_attivita a
                        JOIN at_stati_attivita sa ON a.idstatointervento = sa.idstatointervento
                        WHERE a.parent = ' .prepare($id_activity);

$subactivities = $dbo->fetchArray($query_subactivities);

$color = 'panel-footer';

if (substr_count($j,"-")%2 == 0) { //caso pari
    $color = 'panel-body';
}

$paddingLeft = 15 * (substr_count($j,"-") + 1);
error_log($paddingLeft);

foreach ($subactivities as $i => $activity) {
    if ($activity['codice'] == 'OK') {
        $color = 'bg-success';
    }
    echo '
    <div class="' . $color . '" data-collapseid="' .$j . '-' . ($i+1) . '" style="cursor:pointer;padding:10px 15px;"
            onclick="openCollapse($(this),' . $activity['id'] . ',' . $j.'-'.$i+1 . ')">
        <h4 class="panel-title">
            <a data-toggle="collapse" style="padding-left:' . $paddingLeft . 'px">
                <a href="' . $modulo . '?id_module=' . $id_module . '&id_record=' . $activity['id'] . '">' .tr('Attività ') . $activity['id'] . '</a>
            </a>';
            if ($activity['codice'] == 'OK') {
                echo
                '<i class="fa fa-check text-success"></i>';
            }
        echo
        '</h4>
    </div>
    <div id="collapse' . $j . '-' . ($i+1) . '" class="panel-collapse collapse"></div>';
}
