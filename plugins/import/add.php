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

//get id, name from zz_imports
$id_prima_nota = $dbo->fetchOne('SELECT id FROM zz_imports WHERE name = "Prima nota"')['id'];

?><form action="" method="post" id="add-form" enctype="multipart/form-data">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "file", "label": "<?php echo tr('File'); ?>", "name": "file", "required": 1, "accept": ".csv" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "hidden", "name": "id_import", "required": 1, "value": "<?php echo $id_prima_nota ?>" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button id="example" type="button" class="btn btn-info">
                <i class="fa fa-file"></i> <?php echo tr('Scarica esempio CSV'); ?>
            </button>

			<button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
            </button>
		</div>
	</div>
</form>

<script>
    $(document).ready(function() {
        $('#example').on('click', function (){
            $.ajax({
                url: globals.rootdir + "plugins/import/actions.php",
                type: 'POST',
                data: {
                    op: "example",
                    id_module: globals.id_module,
                    id_import: $('#id_import').val(),
                },
                success: function (data) {
                    if (data) {
                        window.location = data;
                    }

                    $('#main_loading').fadeOut();
                }
            });
        });
    });
</script>
