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

/**
 * Funzione per aggiornare le sedi nei movimenti di magazzino.
 */
function aggiorna_sedi_movimenti($module, $id)
{
    $dbo = database();

    if ($module == 'ddt') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione, dir, data FROM dt_ddt INNER JOIN dt_tipiddt ON dt_tipiddt.id = dt_ddt.idtipoddt WHERE dt_ddt.id='.prepare($id));

        $idsede = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_destinazione'] : $rs[0]['idsede_partenza'];
        $data = $rs[0]['data'];

        $dbo->query('UPDATE mg_movimenti SET idsede='.prepare($idsede).', data='.prepare($data).' WHERE reference_type='.prepare('Modules\DDT\DDT').' AND reference_id='.prepare($id));
    } elseif ($module == 'documenti') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione, dir, data FROM co_documenti INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($id));

        $idsede = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_destinazione'] : $rs[0]['idsede_partenza'];
        $data = $rs[0]['data'];

        $dbo->query('UPDATE mg_movimenti SET idsede='.prepare($idsede).', data='.prepare($data).' WHERE reference_type='.prepare('Modules\Fatture\Fattura').' AND reference_id='.prepare($id));
    } elseif ($module == 'interventi') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione FROM in_interventi WHERE in_interventi.id='.prepare($id));

        $idsede = $rs[0]['idsede_partenza'];

        $dbo->query('UPDATE mg_movimenti SET idsede='.prepare($idsede).' WHERE reference_type='.prepare('Modules\Interventi\Intervento').' AND reference_id='.prepare($id));
    }
}

/**
 * Funzione per aggiornare lo storico dei prezzi degli articoli.
 *
 * @param int   $id_articolo  id articolo
 * @param float $prezzo       nuovo prezzo dell'articolo
 * @param int   $id_listino   id del listino
 * @param int   $id_fornitore id del fornitore
 *
 * @return void
 */
function Aggiorna_storico($id_articolo, $prezzo, $id_listino = null, $tipo_prezzo = null, $id_fornitore = null) {
    $dbo = database();
    $id_utente = Auth::user()['id'];

    $dbo->query(
        'INSERT INTO mg_storico_prezzi_articoli (idarticolo, idutente, idfornitore, idlistino, tipo_prezzo, prezzo)
        VALUES (' .prepare($id_articolo).', '.prepare($id_utente).', '.prepare($id_fornitore).', '.prepare($id_listino).', '.prepare($tipo_prezzo).', '.prepare($prezzo).')'
    );
}
