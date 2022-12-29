<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

switch (filter('op')) {
    case 'update':
        $codice = post('codice');
        $descrizione = post('descrizione');
        $id_modello = post('id_modello');

        $dbo->update('co_movimenti_abi', [
            'codice' => $codice,
            'descrizione' => $descrizione,
            'id_modello' => $id_modello,
        ], [
            'id' => $id_record,
        ]);

        flash()->info(tr('Informazioni aggiornate correttamente!'));

        break;

    case 'add':
        $codice = post('codice');
        $descrizione = post('descrizione');

        $dbo->insert('co_movimenti_abi', [
            'codice' => $codice,
            'descrizione' => $descrizione,
        ]);

        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Nuovo movimento ABI aggiunto!'));

        break;

    case 'delete':
        $dbo->query('DELETE FROM `co_movimenti_abi` WHERE `id`='.prepare($id_record));

        flash()->info(tr('Movimento ABI eliminato con successo!'));

        break;
}
