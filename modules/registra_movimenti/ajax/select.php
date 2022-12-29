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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'scadenze':
        $query = 'SELECT co_scadenziario.id AS id, IF(iddocumento!=0, CONCAT(an_anagrafiche.ragione_sociale, ", Ft n.", numero_esterno, " del ", DATE_FORMAT(co_documenti.data, "%d/%m/%Y"), ", ", (ROUND((da_pagare - pagato), 2)), " €, Scadenza: ", DATE_FORMAT(scadenza, "%d/%m/%Y")), CONCAT(`descrizione`, ", ", (ROUND((da_pagare - pagato), 2)), " €, Scadenza: ", DATE_FORMAT(scadenza, "%d/%m/%Y"))) AS descrizione FROM co_scadenziario LEFT JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id LEFT JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica |where|';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'an_anagrafiche.ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'numero_esterno LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '(da_pagare - pagato) LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'scadenza LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'co_documenti.data LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'co_scadenziario.distinta LIKE '.prepare('%'.$search.'%');
        }
        $importo = $dbo->selectOne('co_registra_movimenti', 'importo', ['id' => $superselect['id']])['importo'];
        if($importo>0){
            $segno = ">";
        } else{
            $segno = "<";
        }
        if (empty($filter)) {
            $where[] = 'ABS(da_pagare - pagato)>0 AND da_pagare'.$segno.'0';
        }
        break;
}
