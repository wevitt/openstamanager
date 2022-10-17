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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'tipiintervento':
        $query = 'SELECT idtipointervento AS id, CASE WHEN ISNULL(tempo_standard) OR tempo_standard <= 0 THEN descrizione WHEN tempo_standard > 0 THEN  CONCAT(descrizione, \' (\', REPLACE(FORMAT(tempo_standard, 2), \'.\', \',\'), \' ore)\') END AS descrizione, tempo_standard FROM in_tipiintervento |where| ORDER BY idtipointervento';

        foreach ($elements as $element) {
            $filter[] = 'idtipointervento='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
    case 'assegnati':
        $current_type = $_SESSION['current_tipo_intervento'][""];

        if ($current_type != "null" || $current_type == 0) {
            $query = "SELECT CONCAT(an_anagrafiche.idanagrafica, '-P') as id,
                CONCAT(
                    ragione_sociale,
                    IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')),
                    IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')
                ) AS descrizione
                FROM an_anagrafiche
                INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche
                ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica)
                ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica
                JOIN zz_users ON zz_users.idanagrafica = an_anagrafiche.idanagrafica
                |where|";


            $where[] = "an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM at_tipi_persone itp WHERE itp.idtipointervento=" . $current_type . ")";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            //$where[] = "descrizione='Tecnico'";
            if (empty($filter)) {
                $where[] = 'deleted_at IS NULL';

                if (setting('Permetti inserimento sessioni degli altri tecnici')) {
                } else {
                    //come tecnico posso aprire attività solo a mio nome
                    $user = Auth::user();
                    if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
                        $where[] = 'an_anagrafiche.idanagrafica='.$user['idanagrafica'];
                    }
                }
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $query .= " UNION ";
            $query .= "SELECT concat(g.id, '-G') as id, g.nome as descrizione
            FROM zz_groups g
            LEFT JOIN at_tipi_gruppi itg ON g.id = itg.idgruppo
            WHERE itg.idtipointervento = '" . $current_type . "'";

            if (!empty($search)) {
                $query .= " AND g.nome LIKE " . prepare("%" . $search . "%");
            }

            $query .= " GROUP BY id, descrizione";
        }
        break;
}
