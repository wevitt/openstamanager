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

use Carbon\Carbon;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $database->update('vb_reparti', [
            'descrizione' => post('descrizione'),
            'is_servizio' => post('is_servizio'),
        ], ['id' => $id_record]);

        flash()->info(tr('Salvataggio completato'));

        break;

    case 'add':
        $descrizione = post('descrizione');
        $codice = post('codice');

        if ($database->fetchNum('SELECT * FROM `vb_reparti` WHERE deleted_at IS NULL AND `codice` = '.prepare($codice)) == 0) {
            $database->insert('vb_reparti', [
                'codice' => $codice,
                'descrizione' => $descrizione,
                'is_servizio' => post('is_servizio'),
            ]);
            $id_record = $database->lastInsertedID();

            flash()->info(tr('Salvataggio completato'));
        } else {
            flash()->error(tr("E' già presente una reparto con lo stesso codice!"));
        }

        break;

    case 'delete':
        if ($numero_collegamenti == 0) {
            //$database->query('DELETE FROM `vb_reparti` WHERE `id` = '.prepare($id_record));
            $database->update('vb_reparti', [
                'deleted_at' => new Carbon(),
            ], ['id' => $id_record]);

            flash()->info(tr('Reparto _CODE_ eliminato con successo', [
                '_CODE_' => $id_record,
            ]));
        } else {
            flash()->error(tr('Sono presenti degli elementi collegati a questo reparto'));
        }

        break;
}
