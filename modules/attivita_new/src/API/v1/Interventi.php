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

namespace Modules\Attivita\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Auth;
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Attivita\Intervento;
use Modules\Attivita\Stato;
use Modules\TipiAttivita\Tipo as TipoSessione;

class Interventi extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface
{
    public function retrieve($request)
    {
        // Periodo per selezionare interventi
        $today = date('Y-m-d');
        $period_end = date('Y-m-d', strtotime($today.' +7 days'));
        $period_start = date('Y-m-d', strtotime($today.' -2 months'));
        $user = Auth::user();

        // AND `at_statiattivita`.`is_completato`=0
        $query = "SELECT `at_attivita`.`id`,
            `at_attivita`.`codice`,
            `at_attivita`.`data_richiesta`,
            `at_attivita`.`richiesta`,
            `at_attivita`.`descrizione`,
            `at_attivita`.`idtipointervento`,
            `at_attivita`.`idanagrafica`,
            `at_attivita`.`idsede_destinazione`,
            `at_attivita`.`idstatointervento`,
            `at_attivita`.`informazioniaggiuntive`,
            `at_attivita`.`idclientefinale`,
            `at_attivita`.`firma_file`,
            IF(firma_data = '0000-00-00 00:00:00', '', firma_data) AS `firma_data`,
            `at_attivita`.firma_nome,
            (SELECT GROUP_CONCAT(CONCAT(my_impianti.matricola, ' - ', my_impianti.nome) SEPARATOR ', ') FROM (my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto=my_impianti.id) WHERE my_impianti_interventi.idintervento = `at_attivita`.`id`) AS `impianti`,
            (SELECT MAX(`orario_fine`) FROM `at_attivita_tecnici` WHERE `at_attivita_tecnici`.`idintervento` = `at_attivita`.`id`) AS `data`,
            (SELECT GROUP_CONCAT(DISTINCT ragione_sociale SEPARATOR ', ') FROM `at_attivita_tecnici` INNER JOIN `an_anagrafiche` ON `at_attivita_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` WHERE `at_attivita_tecnici`.`idintervento` = `at_attivita`.`id`) AS `tecnici`,
            `at_statiattivita`.`colore` AS `bgcolor`,
            `at_statiattivita`.`descrizione` AS `stato`,
            `at_attivita`.`idtipointervento` AS `tipo`
        FROM `at_attivita`
            INNER JOIN `at_statiattivita` ON `at_attivita`.`idstatointervento` = `at_statiattivita`.`idstatointervento`
            INNER JOIN `an_anagrafiche` ON `at_attivita`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            LEFT JOIN `an_sedi` ON `at_attivita`.`idsede_destinazione` = `an_sedi`.`id`
        WHERE EXISTS(SELECT `orario_fine` FROM `at_attivita_tecnici` WHERE `at_attivita_tecnici`.`idintervento` = `at_attivita`.`id` AND `orario_fine` BETWEEN :period_start AND :period_end AND idtecnico LIKE :idtecnico)";

        // Se sono l'admin posso vedere tutte le attività
        $id_anagrafica = $user->is_admin ? '%' : $user->idanagrafica;

        $query .= '
        HAVING 2=2
        ORDER BY `at_attivita`.`data_richiesta` DESC';

        $parameters = [
            ':period_end' => $period_end,
            ':period_start' => $period_start,
            ':idtecnico' => $id_anagrafica,
        ];

        $module = Modules::get('Interventi');

        $query = Modules::replaceAdditionals($module->id, $query);

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];

        $anagrafica = Anagrafica::find($data['id_anagrafica']);
        $tipo = TipoSessione::find($data['id_tipo_intervento']);
        $stato = Stato::find($data['id_stato_intervento']);

        $intervento = Intervento::build($anagrafica, $tipo, $stato, $data['data_richiesta']);

        $intervento->richiesta = $data['richiesta'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();

        return [
            'id' => $intervento->id,
            'codice' => $intervento->codice,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $intervento = Intervento::find($data['id']);

        $intervento->idstatointervento = $data['id_stato_intervento'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();
    }
}
