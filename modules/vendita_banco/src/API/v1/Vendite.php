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

namespace Modules\VenditaBanco\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules;

class Vendite extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        // Periodo per selezionare vendite
        $today = date('Y-m-d');
        $period_end = $request['date_end'] ?: date('Y-m-d', strtotime($today.' +7 days'));
        $period_start = $request['date_start'] ?: date('Y-m-d', strtotime($today.' -2 months'));

        $query = "SELECT `vb_venditabanco`.`id`,
            `vb_venditabanco`.`numero`,
            `vb_venditabanco`.`data`,
            `vb_venditabanco`.`note`,
            `vb_venditabanco`.`idanagrafica`,
            `an_anagrafiche`.`ragione_sociale` AS `anagrafica`,
            `vb_venditabanco`.`idstato`,
            `vb_stati_vendita`.`descrizione` AS `stato`,
            `vb_venditabanco`.`idpagamento`,
            `co_pagamenti`.`descrizione` AS `pagamento`
        FROM `vb_venditabanco`
            LEFT JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
            LEFT JOIN `an_anagrafiche` ON `vb_venditabanco`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            LEFT JOIN `co_pagamenti` ON `vb_venditabanco`.`idpagamento` = `co_pagamenti`.`id`
        WHERE `data` BETWEEN :period_start AND :period_end";

        $query .= '
        HAVING 2=2
        ORDER BY `vb_venditabanco`.`data` DESC';

        $parameters = [
            ':period_end' => $period_end,
            ':period_start' => $period_start,
        ];

        $module = Modules::get('Vendita al banco');

        $query = Modules::replaceAdditionals($module->id, $query);

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }
}
