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

namespace API\App\v1;

use API\App\AppResource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Anagrafiche\Anagrafica;

class Tecnici extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return $this->getDeleted('an_anagrafiche', 'idanagrafica', $last_sync_at);
    }

    public function getModifiedRecords($last_sync_at)
    {
        $statement = Anagrafica::select('idanagrafica', 'updated_at')
            ->whereHas('tipi', function (Builder $query) {
                $query->where('descrizione', '=', 'Tecnico');
            });

        // Filtro per data
        if ($last_sync_at) {
            $statement = $statement->where('updated_at', '>', $last_sync_at);
        }

        $records = $statement->get();

        return $this->mapModifiedRecords($records);
    }

    public function retrieveRecord($id)
    {
        // Gestione della visualizzazione dei dettagli del record
        $query = 'SELECT an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.ragione_sociale
        FROM an_anagrafiche
        WHERE an_anagrafiche.idanagrafica = '.prepare($id);

        $record = database()->fetchOne($query);

        return $record;
    }
}
