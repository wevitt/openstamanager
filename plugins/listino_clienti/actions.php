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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\ListinoClienti\DettaglioPrezzo;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update_prezzi':
        // Informazioni di base
        $id_articolo = filter('id_articolo');
        $id_anagrafica = filter('id_anagrafica');
        $direzione = filter('dir') == 'uscita' ? 'uscita' : 'entrata';

        $articolo = Articolo::find($id_articolo);
        $anagrafica = Anagrafica::find($id_anagrafica);

        $modifica_prezzi = filter('modifica_prezzi');
        if (empty($modifica_prezzi)) {
            $dbo->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo='.prepare($id_articolo).' AND id_anagrafica='.prepare($id_anagrafica).' AND minimo IS NULL AND massimo IS NULL');

            if ($id_anagrafica == $articolo->id_fornitore && $direzione == 'uscita') {
                $articolo->id_fornitore = null;
                $articolo->save();
            }
        } else {
            // Salvataggio del prezzo predefinito
            $prezzo_unitario = filter('prezzo_unitario_fisso');
            $sconto = filter('sconto_fisso');
            $dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
                ->first();
            if (empty($dettaglio_predefinito)) {
                $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
            }

            if ($dettaglio_predefinito->sconto_percentuale != $sconto || $dettaglio_predefinito->prezzo_unitario != $prezzo_unitario) {
                $dettaglio_predefinito->sconto_percentuale = $sconto;
                $dettaglio_predefinito->setPrezzoUnitario($prezzo_unitario);
                $dettaglio_predefinito->save();
                if ($articolo->id_fornitore == $anagrafica->idanagrafica && $direzione == 'uscita') {
                    $prezzo_unitario = $prezzo_unitario - ($prezzo_unitario * $sconto / 100);
                    $articolo->prezzo_acquisto = $prezzo_unitario;
                    $articolo->save();
                }
            }
        }

        // Salvataggio dei prezzi variabili
        $prezzo_qta = filter('prezzo_qta');
        $dettagli = DettaglioPrezzo::dettagli($id_articolo, $id_anagrafica, $direzione);
        if (!empty($prezzo_qta)) {
            $prezzi_unitari = (array) filter('prezzo_unitario');
            $minimi = filter('minimo');
            $massimi = filter('massimo');
            $sconti = (array) filter('sconto');

            // Rimozione dei prezzi cancellati
            $registrati = filter('dettaglio');
            if (!empty($registrati)) {
                $dettagli = $dettagli->whereNotIn('id', $registrati)->delete();
            }

            // Aggiornamento e creazione dei prezzi registrati
            foreach ($prezzi_unitari as $key => $prezzo_unitario) {
                if (isset($registrati[$key])) {
                    $dettaglio = DettaglioPrezzo::find($registrati[$key]);
                } else {
                    $dettaglio = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
                }

                if ($dettaglio->minimo != $minimi[$key] || $dettaglio->massimo != $massimi[$key] || $dettaglio->sconto_percentuale != $sconti[$key] || $dettaglio->prezzo_unitario != $prezzo_unitario) {
                    $dettaglio->minimo = $minimi[$key];
                    $dettaglio->massimo = $massimi[$key];
                    $dettaglio->sconto_percentuale = $sconti[$key];
                    $dettaglio->setPrezzoUnitario($prezzo_unitario);
                    $dettaglio->save();
                }
            }
        } else {
            $dettagli->delete();
        }

        break;
}
