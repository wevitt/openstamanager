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

class Righe extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT id, idarticolo AS id_articolo, idvendita, idiva, desc_iva, descrizione, qta, id_reparto, created_at as data FROM `vb_righe_venditabanco` WHERE `idvendita` = :id_vendita';

        $parameters = [
            ':id_vendita' => $request['id_vendita'],
        ];

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }
}
