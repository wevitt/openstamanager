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

// Messaggio informativo per variante
if ($articolo->isVariante()) {
    echo '
<div class="badge badge-info">'.tr('Variante: _NAME_', [
    '_NAME_' => $articolo->nome_variante,
]).'</div>';
}

echo '
<button type="button" class="btn btn-primary" onclick="duplicaArticolo()">
    <i class="fa fa-copy"></i> '.tr('Duplica articolo').'
</button>

<script>
function duplicaArticolo() {
    openModal("'.tr('Duplica articolo').'", "'.$module->fileurl('modals/duplicazione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}
</script>';
