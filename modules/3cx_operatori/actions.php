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
    case 'add':
        $interno = post('interno');
        $controllo = $dbo->fetchNum('SELECT * FROM `3cx_operatori` WHERE deleted_at IS NULL AND `interno`='.prepare($interno));
        if ($controllo == 0) {
            $database->insert('3cx_operatori', [
                'interno' => $interno,
                'id_anagrafica' => post('id_anagrafica'),
            ]);

            flash()->info(tr('Aggiunta nuovo operatore'));
        } else {
            flash()->error(tr("Esiste un operatore con l'interno indicato!"));
        }

        break;

    case 'delete':
        $database->query('UPDATE 3cx_operatori SET deleted_at = NOW() WHERE `id`='.prepare($id_record));

        break;
}
