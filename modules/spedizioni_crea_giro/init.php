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

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM `co_documenti` WHERE id = '. prepare($id_record));
    $type = 'co_documenti';

    if (!$record) {
        $record = $dbo->fetchOne('SELECT * FROM `dt_ddt` WHERE id = '. prepare($id_record));
        $type = 'ddt';
    }
}
?>

<!--<input type="hidden" class="record" value="<?= $id_record ?>">

<script>
function get() {
    var $rows = $(".dataTables_scrollBody").find("tbody");
    var record = $('.record').val();

    $rows.find(".selected").each(function() {
        var tipo_consegna;

        if ($(this).find('span').attr('data-id') == record) {
            tipo_consegna = $(this).find("td:nth-child(2)").text();
        }
    });
}
</script>-->
