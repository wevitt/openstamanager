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
?>

<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="riconsegna">

    <input type="hidden" name="id_consegna" value="<?= filter('id_consegna') ?>">
    <input type="hidden" name="id_module" value="<?= filter('id_module') ?>">
    <input type="hidden" name="id_record" value="<?= filter('id_record') ?>">
    <input type="hidden" class="current-state" value="<?= filter('stato') ?>">

    <div class="row" style="margin:15px">
        <select class="form-control" name="riconsegna">
            <option value="0"></option>
            <option value="1">Riconsegna per problematiche interne/cliente</option>
            <option value="2">Riconsegna per problematiche del corriere</option>
        </select><br>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary"><?php echo tr('Riconsegna');?></button>
		</div>
	</div>
</form>

<script>
	$(document).ready(function() {
        var stato = $('.current-state').val();
        $('select').val(stato).change();
    });
</script>
