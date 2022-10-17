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

namespace Centralino3CX\API;

use API\Interfaces\CreateInterface;
use API\Resource;
use Carbon\Carbon;
use Centralino3CX\Chiamata;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class CallJournaling extends Resource implements CreateInterface
{
    public function create($request)
    {
        $numero = Chiamata::filtraNumero($request['Number']);
        $in_entrata = intval($request['Inbound']);

        $inizio = new Carbon($request['CallStartTimeLocal']);
        $fine = new Carbon($request['CallEndTimeLocal']);
        $risposta = intval($request['Answered']);

        // Ricerca della chiamata corrente tra quelle in corso
        // Chiamate dello stesso numero, di durata 0 e non gestite con notifica di inizio entro 5 minuti dall'inizio effettivo
        // 5 minuti = durata massima segreteria di attesa
        $chiamate_in_corso = Chiamata::where('in_entrata', '=', $in_entrata)
            ->where('durata', '=', 0)
            ->where('is_gestito', '=', 0)
            ->where(function ($query) use ($numero) {
                $query->where('numero', 'like', '%'.$numero)
                    ->orWhereRaw('numero = RIGHT(?, CHAR_LENGTH(numero))', [$numero]);
            })
            ->where('inizio', '>=', $inizio->subMinutes(3))
            ->orderBy('id')
            ->get();

        // Rimozione chiamate più recenti poichè duplicazioni causate dalla cache di 3CX
        // 3CX effettua un lookup quando la cache diventa non valida (durata chiamata >= 10 minuti)
        $chiamata = $chiamate_in_corso->first();
        foreach ($chiamate_in_corso as $chiamata_duplicata) {
            if ($chiamata_duplicata->id !== $chiamata->id) {
                $chiamata_duplicata->delete();
            }
        }

        // Registrazione chiamata come nuova se non trovata
        if (empty($chiamata)) {
            $chiamata = Chiamata::build($numero, $in_entrata);
        }

        // Completamento informazioni sulla chiamata
        $chiamata->interno = $request['Agent'];
        $chiamata->inizio = $inizio;
        $chiamata->fine = $fine;
        $chiamata->tipo = $request['CallType'];
        $chiamata->durata = $request['DurationSeconds'];
        $chiamata->durata_visibile = $request['Duration'];
        $chiamata->is_risposta = $risposta;
        $chiamata->oggetto = $request['Description'];
        $chiamata->numero_journaling = $request['Number'];

        $chiamata->save();

        // Aggiunta sessione di chiamata all'intervento collegato (se presente)
        $intervento = $chiamata->intervento;
        $tecnico = $chiamata->tecnico;
        if ($risposta && !empty($intervento)) {
            $codice_tipo = 'CALL';
            $tipo = TipoSessione::where('codice', '=', $codice_tipo)->first();

            // Creazione sessione di tipo CALL se esistente
            if (!empty($tipo)) {
                $sessione = Sessione::build($intervento, $tecnico, $inizio, $fine);
                $sessione->setTipo($tipo->id, true);
                $sessione->save();
            }
        }

        return [];
    }

    protected function crea()
    {
        $risposta = false;
        // Creazione intervento collegato al tecnico per chiamate con risposta
        if ($risposta) {
            $codice_tipo = 'CALL';
            $tipo = TipoSessione::where('codice', '=', $codice_tipo)->first();
            $stato = Stato::where('codice', '=', 'WIP')->first();

            // Ricerca cliente
            $cliente = Anagrafica::find($id_anagrafica);

            // Ricerca tecnico associato
            $id_tecnico = $database->fetchOne('SELECT id_anagrafica FROM 3cx_operatori WHERE deleted_at IS NULL AND interno = '.prepare($interno))['id_anagrafica'];
            $tecnico = Anagrafica::find($id_tecnico);

            if (!empty($tipo) && !empty($stato) && !empty($cliente) && !empty($tecnico) && $cliente->isTipo('Cliente')) {
                $intervento = Intervento::where('idanagrafica', '=', $cliente->id)
                    ->whereHas('stato', function ($query) {
                        $query->where('is_completato', '=', 0);
                    })
                    ->whereHas('tipo', function ($query) use ($codice_tipo) {
                        $query->where('codice', '=', $codice_tipo);
                    })
                    ->where('data_richiesta', '>', Carbon::now()->subHours(2))
                    ->first();

                // Creazione intervento
                if (empty($intervento)) {
                    $intervento = Intervento::build($cliente, $tipo, $stato, Carbon::parse($inizio));
                    $intervento->richiesta = $descrizione;
                    $intervento->save();
                } else {
                    $intervento->richiesta = $intervento->richiesta.'<br>'.$descrizione;
                }

                // Creazione sessione dell'intervento
                $sessione = Sessione::build($intervento, $tecnico, $inizio, $fine);
                $sessione->save();

                // Collegamento intervento - chiamata
                $database->update('3cx_chiamate', [
                    'id_intervento' => $intervento->id,
                ], ['id' => $id]);
            }
        }
    }
}
