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

switch (filter('op')) {
    case 'update':
        $nome = filter('nome_percorso');
        $note = filter('note');

        if (isset($nome)) {
            if ($dbo->fetchNum('SELECT * FROM `sp_spedizioni` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query(
                    'UPDATE `sp_spedizioni`
                    SET `nome_percorso`='.prepare($nome). ', `note`='.prepare($note).
                    ' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione _NAME_.", [
                    '_NAME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'riconsegna':
        $id = filter('id_consegna');
        $stato = filter('riconsegna');

        $id_record = filter('id_record');

        if (isset($id)) {
            if ($dbo->fetchNum('SELECT * FROM `sp_spedizioni_dettaglio` WHERE `id` = ' . prepare($id)) != 0) {
                $dbo->query('UPDATE `sp_spedizioni_dettaglio` SET `stato` = ' . prepare($stato) . ' WHERE `id` = ' . prepare($id));

                $id_record = $dbo->lastInsertedID();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $stato]);
                }

                flash()->info(tr('Modificato stato consegna _NAME_', [
                    '_NAME_' => $stato,
                ]));
            } else {
                flash()->error(tr("Non e' stata trovata la consegna numero _NAME_.", [
                    '_NAME_' => $id,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'delete':
        $righe = $dbo->fetchNum('SELECT * FROM `sp_spedizioni` WHERE id`='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `sp_spedizioni_dettaglio` WHERE `id_spedizione`='.prepare($id_record));
            $dbo->query('DELETE FROM `sp_spedizioni` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Relazione _NAME_ eliminata con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questa relazione.'));
        }

        break;
}
