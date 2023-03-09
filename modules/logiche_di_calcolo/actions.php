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
        $listino_di_origine = post('listino_di_origine');
        $listino_di_destinazione = post('listino_di_destinazione');
        $formula_da_applicare = post('formula_da_applicare');

        //controllo se esiste già una logica di calcolo per il listino di origine e di destinazione
        $rs = $dbo->fetchArray('SELECT * FROM `mg_logiche_calcolo` WHERE `id_listino_origine`='.prepare($listino_di_origine).' AND `id_listino_destinazione`='.prepare($listino_di_destinazione));
        if (!empty($rs)) {
            flash()->error(tr('Esiste gia\' una logica di calcolo per questo listino di origine e di destinazione!'));
        } else {
            //insert into mg_logiche di calcolo
            $dbo->query(
                'INSERT INTO `mg_logiche_calcolo` (`id_listino_origine`, `id_listino_destinazione`, `formula_da_applicare`)
                VALUES ('.prepare($listino_di_origine).', '.prepare($listino_di_destinazione).', '.prepare($formula_da_applicare).')'
            );

            flash()->info(tr('Aggiunta nuova logica di calcolo!'));
        }

        break;

    case 'update':
        $listino_di_origine = post('listino_di_origine');
        $listino_di_destinazione = post('listino_di_destinazione');
        $formula_da_applicare = post('formula_da_applicare');

        if (isset($listino_di_origine) && isset($listino_di_destinazione) && isset($formula_da_applicare)) {
            $dbo->query(
                'UPDATE `mg_logiche_calcolo`
                SET `formula_da_applicare`='.prepare($formula_da_applicare).'
                WHERE `id`='.prepare($id_record)
                );
        } else {
            flash()->error(tr("Indicare la formula da applicare!"));
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query(
                'DELETE FROM `mg_logiche_calcolo` WHERE `id`='.prepare($id_record)
            );

            flash()->info(tr('Relazione eliminata con successo!'));
        }

        break;
}
