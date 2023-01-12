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

if (isset($id_record)) {
    $record = $dbo->fetchOne(
        'SELECT mg_logiche_calcolo.id, mg_logiche_calcolo.formula_da_applicare,
        origine.nome as listino_origine, destinazione.nome as listino_destinazione
        FROM `mg_logiche_calcolo`
        LEFT JOIN mg_listini as origine ON mg_logiche_calcolo.id_listino_origine = origine.id
        LEFT JOIN mg_listini as destinazione ON mg_logiche_calcolo.id_listino_destinazione = destinazione.id
        WHERE mg_logiche_calcolo.id = '. prepare($id_record)
    );
}
