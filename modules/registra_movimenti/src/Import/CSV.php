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

namespace Modules\RegistraMovimenti\Import;

use Carbon\Carbon;
use Importer\CSVImporter;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Articoli.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'data',
                'label' => 'Data',
                'names' => [
                    'Data Contabile',
                ],
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
                'names' => [
                    'Descrizioni Aggiuntive',
                ],
            ],
            [
                'field' => 'dare',
                'label' => 'Dare',
                'names' => [
                    'Dare',
                ],       
            ],
            [
                'field' => 'avere',
                'label' => 'Avere',
                'names' => [
                    'Avere',
                ],       
            ],
            [
                'field' => 'importo',
                'label' => 'Importo',
                'names' => [
                    'Importo',
                    'Totale',
                ],            
            ],
            [
                'field' => 'codice_abi',
                'label' => 'Codice ABI',
                'names' => [
                    'Causale ABI/Swift',
                    'Causale ABI',
                    'ABI',
                ],            
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $data = str_replace('/', '-', $record['data']);
        $data = date("Y-m-d",strtotime($data));
        $data = Carbon::parse($data);
        
        $record['dare'] = str_contains($record['dare'], ',') ? str_replace(',', '.', str_replace('.', '', $record['dare'])) : $record['dare'];
        $record['avere'] = str_contains($record['avere'], ',') ? str_replace(',', '.', str_replace('.', '', $record['avere'])) : $record['avere'];
        $record['importo'] = str_contains($record['importo'], ',') ? str_replace(',', '.', str_replace('.', '', $record['importo'])) : $record['importo'];

        //Importo i dati se non già presenti
        $prev = $database->table('co_registra_movimenti')
            ->where('data',$data)
            ->where('descrizione',$record['descrizione'])
            ->first();

        //if( empty($prev) ){
            if(empty(preg_match('/\S/', $record['importo']))){
                if(!empty(preg_match('/\S/', $record['dare']))){
                    $importo = $record['dare'];
                } else{
                    $importo = -$record['avere'];
                }
            } else{
                $importo = $record['importo'];
            }
            
            $database->insert('co_registra_movimenti', 
            [
                'data' => $data, 
                'descrizione' => $record['descrizione'],
                'importo' => str_replace(',','.',$importo),
                'codice_abi' => $record['codice_abi'],
            ]);
        //}
    }

    public static function getExample()
    {
        return [
            ['Data Contabile', 'Descrizione', 'Dare', 'Avere', 'Causale ABI(facoltativo)'],
            ['01/01/2021', 'Pagamento fattura n.1 del...', '0', '10', '1'],
            [' '],
            ['OPPURE'],
            [' '],
            ['Data Contabile', 'Descrizione', 'Importo', 'Causale ABI(facoltativo)'],
            ['01/01/2021', 'Pagamento fattura n.1 del...', '-10', '1'],
        ];
    }
}
