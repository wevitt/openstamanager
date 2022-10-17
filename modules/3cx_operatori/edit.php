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
<script src="https://demo.osmcloud.it/2423/modules/3cx_centralino/ajax/caller.js"></script>
<!-- DATI -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                {[ "type": "text", "label": "<?php echo tr('Interno'); ?>", "name": "id", "disabled": 1, "value": "$interno$" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "<?php echo tr('Operatore'); ?>", "name": "id_anagrafica", "ajax-source": "tecnici", "disabled": 1, "value": "$id_anagrafica$" ]}
            </div>
        </div>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
