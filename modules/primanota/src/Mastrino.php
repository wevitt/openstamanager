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

namespace Modules\PrimaNota;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;
/*
 * Struttura ausiliaria dedicata alla raggruppamento e alla gestione di un insieme di Movimenti, unificati attraverso il numero di mastrino.
 *
 * Questa classe non è utilizzabile come normale modello Eloquent poichè non prevede operazioni di modifica a livello di database.
 * La creazione di un record può essere utilizzata per la gestione di un insieme di Movimenti, mentre l'eliminazione provoca la rimozione in cascata dei Movimenti associati al Mastrino.
 */
use Modules\Scadenzario\Scadenza;

class Mastrino extends Model
{
    use SimpleModelTrait;

    public $incrementing = false;
    protected $table = 'co_movimenti';
    protected $primaryKey = 'idmastrino';

    protected $hidden = [
        'idmastrino',
        'data_documento',
        'iddocumento',
        'id_anagrafica',
    ];

    public static function build($descrizione, $data, $is_insoluto = false, $contabile = false, $id_anagrafica = null)
    {
        $model = new static();

        $model->idmastrino = self::getNextMastrino();
        $model->data = $data;
        $model->descrizione = $descrizione;
        $model->is_insoluto = $is_insoluto;
        $model->primanota = $contabile;
        $model->id_anagrafica = $id_anagrafica;

        return $model;
    }

    /**
     * Rimuove tutti i movimenti collegati al mastrino.
     *
     * @return mixed
     */
    public function cleanup()
    {
        $movimenti = $this->movimenti;
        foreach ($movimenti as $movimento) {
            $movimento->delete();
        }

        return $movimenti;
    }

    public function save(array $options = [])
    {
        return true;
    }

    public function delete()
    {
        $movimenti = $this->cleanup();
        $this->aggiornaScadenzario($movimenti);

        return parent::delete();
    }

    // Attributi

    public function getIdAttribute()
    {
        return $this->idmastrino;
    }

    public function getTotaleAttribute()
    {
        $movimenti = $this->movimenti->where('totale', '>', 0);

        $totale = $movimenti->sum('totale');

        return $totale;
    }

    // Metodi generali

    public function aggiornaScadenzario($movimenti = null)
    {
        // Aggiornamento dello scadenzario disponibile solo da Mastrino di PrimaNota
        if (empty($this->primanota)) {
            return;
        }
        $movimenti = $movimenti ?: $this->movimenti;

        // Aggiornamento delle scadenze per i singoli documenti
        $documenti = [];
        $scadenze = [];
        foreach ($movimenti as $movimento) {
            $scadenza = $movimento->scadenza;
            $documento = $movimento->documento;

            // Retrocompatibilità per versioni <= 2.4.11
            if (!empty($documento)) {
                if (!in_array($documento->id, $documenti)) {
                    $documenti[] = $documento->id;

                    $this->correggiScadenza($movimento, $scadenza, $documento);
                }
            } elseif (!empty($scadenza)) {
                $id_documento = $scadenza->documento->id;

                if (!in_array($id_documento, $documenti) && !in_array($scadenza->id, $scadenze)) {
                    if (!empty($id_documento)) {
                        $documenti[] = $id_documento;
                    }
                    $scadenze[] = $scadenza->id;

                    $this->correggiScadenza($movimento, $scadenza);
                }
            }
        }

        // Fix dello stato della Fattura
        $database = database();
        foreach ($documenti as $id_documento) {
            // Verifico se la fattura è stata pagata tutta, così imposto lo stato a "Pagato"
            $totali = $database->fetchOne('SELECT SUM(pagato) AS tot_pagato, SUM(da_pagare) AS tot_da_pagare FROM co_scadenziario WHERE iddocumento='.prepare($id_documento));

            $totale_pagato = abs(floatval($totali['tot_pagato']));
            $totale_da_pagare = abs(floatval($totali['tot_da_pagare']));

            // Aggiorno lo stato della fattura
            if ($totale_pagato == $totale_da_pagare) {
                $stato = 'Pagato';
            } elseif ($totale_pagato != $totale_da_pagare && $totale_pagato != 0) {
                $stato = 'Parzialmente pagato';
            } else {
                $stato = 'Emessa';
            }

            $database->query('UPDATE co_documenti SET idstatodocumento = (SELECT id FROM co_statidocumento WHERE descrizione = '.prepare($stato).') WHERE id = '.prepare($id_documento));
        }
    }

    // Relazioni Eloquent

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }

    public function movimenti()
    {
        return $this->hasMany(Movimento::class, 'idmastrino');
    }

    // Metodi statici

    public static function getNextMastrino()
    {
        $ultimo = database()->fetchOne('SELECT MAX(idmastrino) AS max FROM co_movimenti');

        return intval($ultimo['max']) + 1;
    }

    /**
     * Funzione dedicata alla distribuzione del totale pagato del movimento nelle relative scadenze associate.
     */
    protected function correggiScadenza(Movimento $movimento, Scadenza $scadenza = null, Fattura $documento = null)
    {
        $is_nota = false;
        $documento = $documento ?: $scadenza->documento;

        // Gestione delle scadenze di un documento
        if ($documento) {
            $dir = $documento->direzione;
            $scadenze = $documento->scadenze->sortBy('scadenza');

            $movimenti = $documento->movimentiContabili;

            if ($dir == 'entrata') {
                $totale_movimenti = $movimenti->where('totale', '<', 0)->where('is_insoluto', 0)->sum('totale');
                $totale_insoluto = $movimenti->where('totale', '<', 0)->where('is_insoluto', 1)->sum('totale');
            }

            if ($dir == 'uscita') {
                $totale_movimenti = $movimenti->where('totale', '>', 0)->where('is_insoluto', 0)->sum('totale');
                $totale_insoluto = $movimenti->where('totale', '>', 0)->where('is_insoluto', 1)->sum('totale');
            }

            $totale_da_distribuire = $totale_movimenti - $totale_insoluto;
            $is_nota = $documento->isNota();
        }

        // Gestione di una singola scadenza
        else {
            $scadenze = [$scadenza];
            $dir = $movimento->totale < 0 ? 'uscita' : 'entrata';

            $totale_da_distribuire = Movimento::where('id_scadenza', '=', $scadenza->id)
                ->where('totale', '>', 0)
                ->sum('totale');
        }

        $totale_da_distribuire = abs($totale_da_distribuire);

        // Ciclo tra le rate dei pagamenti per inserire su `pagato` l'importo effettivamente pagato
        // Nel caso il pagamento superi la rata, devo distribuirlo sulle rate successive
        foreach ($scadenze as $scadenza) {
            $scadenza_da_pagare = abs($scadenza['da_pagare']);

            // Nel caso in cui il totale da distribuire sia stato esaurito, imposta il pagato a zero
            if ($totale_da_distribuire <= 0) {
                $pagato = 0;
            }

            // Se il totale da distribuire è superiore al valore da pagare della scadenza, completa il pagamento
            elseif ($totale_da_distribuire >= $scadenza_da_pagare) {
                $pagato = $scadenza_da_pagare;
                $totale_da_distribuire -= $scadenza_da_pagare;
            }

            // In caso alternativo, assegno il rimanente da distribuire interamente alla scadenza
            else {
                $pagato = $totale_da_distribuire;
                $totale_da_distribuire = 0;
            }

            // Inversione di segno per la direzione del movimento contabile
            $pagato = $dir == 'uscita' ? -$pagato : $pagato;
            $pagato = $is_nota ? -$pagato : $pagato; // Inversione di segno per le note

            // Salvataggio delle informazioni
            $scadenza->pagato = $pagato;
            $scadenza->data_pagamento = $pagato ? $this->data : null;
            $scadenza->save();
        }
    }
}
