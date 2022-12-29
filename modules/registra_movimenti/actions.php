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

use Modules\Fatture\Fattura;
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;
use Modules\Scadenzario\Scadenza;

switch (filter('op')) {
    case 'ignora':
        $id_record = post('id_record');
        $processed_at = date('Y-m-d H:i:s', time());

        $dbo->update('co_registra_movimenti',[
            'processed_at' => $processed_at,
        ], [
            'id' => $id_record,
        ]);

        flash()->info(tr('Movimento rimosso dalla lista!'));
    break;

    case 'add':
        $data = post('data');
        $descrizione = post('descrizione');
        $is_insoluto = post('is_insoluto');
        $id_anagrafica = post('id_anagrafica');
        $mastrino = Mastrino::build($descrizione, $data, $is_insoluto, true, $id_anagrafica);

        $conti = post('idconto');
        foreach ($conti as $i => $id_conto) {
            $id_scadenza = post('id_scadenza')[$i];
            $id_documento = post('id_documento')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];
            $scadenza = Scadenza::find($id_scadenza);
            $fattura = Fattura::find($id_documento);

            $movimento = Movimento::build($mastrino, $id_conto, $fattura, $scadenza);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        $mastrino->aggiornaScadenzario();

        $id_record = $mastrino->id;

        flash()->info(tr('Movimento aggiunto in prima nota!'));

        // Creo il modello di prima nota
        if (!empty(post('crea_modello'))) {
            if (empty(post('idmastrino'))) {
                $idmastrino = get_new_idmastrino('co_movimenti_modelli');
            } else {
                $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare(post('idmastrino')));
                $idmastrino = post('idmastrino');
            }

            foreach ($conti as $i => $id_conto) {
                $idconto = post('idconto')[$i];
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($descrizione).', '.prepare($id_conto).')';
                $dbo->query($query);
            }
        }

        $dbo->update('co_registra_movimenti', [
            'processed_at' => date('Y-m-d H:i:s', time()),
        ], [
            'id' => post('id_record'),
        ]);

        break;

    case 'compila':
        
        $movimenti = $dbo->select('co_registra_movimenti', '*', [
            'processed_at' => NULL,
        ]);
        $scadenze = $dbo->fetchArray('SELECT co_scadenziario.*, IF(iddocumento!=0, CONCAT(an_anagrafiche.ragione_sociale, ", Ft n.", numero_esterno, " del ", DATE_FORMAT(co_documenti.data, "%d/%m/%Y"), ", ", (ROUND((da_pagare - pagato), 2)), " €, Scadenza: ", DATE_FORMAT(scadenza, "%d/%m/%Y")), CONCAT(`descrizione`, ", ", (ROUND((da_pagare - pagato), 2)), " €, Scadenza: ", DATE_FORMAT(scadenza, "%d/%m/%Y"))) AS descrizione, (da_pagare-pagato) AS totale FROM co_scadenziario LEFT JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id LEFT JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE ABS(da_pagare-pagato)>0 ORDER BY scadenza ASC');
        
        foreach($movimenti as $movimento){
            foreach($scadenze as $scadenza){
                if($movimento['importo']==$scadenza['totale']){
                    if(!in_array($scadenza['id'], $result)){         
                        $result[$movimento['id']]['id'] = $scadenza['id'];
                        $result[$movimento['id']]['descrizione'] = $scadenza['descrizione'];
                        break;
                    }
                }
            }
        }
        echo json_encode($result);
        break;

        case 'rimuovi_all':
        $processed_at = date('Y-m-d H:i:s', time());
        $movimenti = $dbo->select('co_registra_movimenti', 'id', [
            'processed_at' => NULL,
        ]);

        foreach($movimenti as $movimento){
            $lista[] = $movimento['id'];
        }

        $dbo->query('UPDATE co_registra_movimenti SET processed_at='.prepare($processed_at).' WHERE id IN ('.implode(", ", $lista).')');

        break;

}