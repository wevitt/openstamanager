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

use Modules\Attivita\Attivita as Intervento;

if (isset($id_record)) {
    $intervento = Intervento::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
       (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = at_attivita.idanagrafica) AS tipo_anagrafica,
       (SELECT is_completato FROM at_stati_attivita WHERE idstatointervento=at_attivita.idstatointervento) AS flag_completato,
       IF((at_attivita.idsede_destinazione = 0), (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = at_attivita.idanagrafica), (SELECT idzona FROM an_sedi WHERE id = at_attivita.idsede_destinazione)) AS idzona,
       (SELECT colore FROM at_stati_attivita WHERE idstatointervento=at_attivita.idstatointervento) AS colore,
       at_attivita.id_preventivo as idpreventivo,
       at_attivita.id_contratto as idcontratto,
       at_attivita.id_ordine as idordine
    FROM at_attivita WHERE id='.prepare($id_record));
}
