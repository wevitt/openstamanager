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

$module = Modules::get($id_module);
?>

<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

    <div class="panel panel-primary">
       <div class="panel-heading">
           <h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
       </div>

       <div class="panel-body">
           <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Listino di origine'); ?>", "name": "listino_di_origine", "readonly":1, "value": "<?php echo $record['listino_origine']; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Listino di destinazione'); ?>", "name": "listino_di_destinazione", "readonly":1, "value": "<?php echo $record['listino_destinazione']; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "<?php echo tr('Formula da applicare'); ?>", "name": "formula_da_applicare", "required": 1, "value": "<?php echo $record['formula_da_applicare']; ?>" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
