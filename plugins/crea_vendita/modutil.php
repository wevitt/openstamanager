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

use Modules\Interventi\Intervento;
use Modules\VenditaBanco\Components\Riga;
use Modules\VenditaBanco\Vendita;

function aggiungi_intervento_in_vendita($id_intervento, $id_vendita, $ore = null, $diritto = null, $km = null)
{
    $dbo = database();

    $vendita = Vendita::find($id_vendita);
    $intervento = Intervento::find($id_intervento);

    if (!empty($vendita->anagrafica->idiva_vendite)) {
        $id_iva = $vendita->anagrafica->idiva_vendite;
    } else {
        $id_iva = setting('Iva predefinita');
    }

    $data = $intervento->inizio;
    $codice = $intervento->codice;

    if (!empty($ore)) {
        // Ore di lavoro raggruppate per costo orario
        $sessioni = $intervento->sessioni;

        if (empty($sessioni)) {
            flash()->warning(tr("L'intervento _NUM_ non ha sessioni di lavoro!", [
                '_NUM_' => $codice,
            ]));
        } else {
            $decimals = setting('Cifre decimali per quantità');

            $ore_di_lavoro = $sessioni->groupBy(function ($item, $key) {
                return $item['prezzo_orario'].'|'.$item['sconto_unitario'].'|'.$item['tipo_sconto'];
            });
            foreach ($ore_di_lavoro as $gruppo) {
                $sessione = $gruppo->first();
                $riga = Riga::build($vendita);

                $riga->descrizione = tr("Ore di lavoro dell'intervento _NUM_ del _DATE_", [
                    '_NUM_' => $codice,
                    '_DATE_' => dateFormat($data),
                ]);
                $riga->um = 'ore';

                $riga->id_iva = $id_iva;
                $riga->idintervento = $id_intervento;
                $riga->prezzo_unitario = $sessione->prezzo_orario;
                $riga->sconto_unitario = $sessione->sconto_unitario;
                $riga->tipo_sconto = $sessione->tipo_sconto;

                $qta_gruppo = $gruppo->sum('ore');
                $riga->qta = round($qta_gruppo, $decimals);

                $riga->save();
            }

            if (!empty($diritto)) {
                // Diritti di chiamata raggruppati per costo
                $diritti_chiamata = $sessioni->where('prezzo_diritto_chiamata', '>', 0)->groupBy(function ($item, $key) {
                    return $item['prezzo_diritto_chiamata'];
                });
                foreach ($diritti_chiamata as $gruppo) {
                    $diritto_chiamata = $gruppo->first();
                    $riga = Riga::build($vendita);

                    $riga->descrizione = tr("Diritto di chiamata dell'intervento _NUM_ del _DATE_", [
                        '_NUM_' => $codice,
                        '_DATE_' => dateFormat($data),
                    ]);
                    $riga->um = 'ore';
                    $riga->idintervento = $id_intervento;
                    $riga->id_iva = $id_iva;
                    $riga->prezzo_unitario = $diritto_chiamata->prezzo_diritto_chiamata;
                    $riga->qta = $gruppo->count();

                    $riga->save();
                }
            }

            if (!empty($km)) {
                // Viaggi raggruppati per costo
                $viaggi = $sessioni->where('prezzo_km_unitario', '>', 0)->groupBy(function ($item, $key) {
                    return $item['prezzo_km_unitario'].'|'.$item['scontokm_unitario'].'|'.$item['tipo_scontokm'];
                });
                foreach ($viaggi as $gruppo) {
                    $qta_trasferta = $gruppo->sum('km');
                    if ($qta_trasferta == 0) {
                        continue;
                    }

                    $viaggio = $gruppo->first();
                    $riga = Riga::build($vendita);

                    $riga->descrizione = tr("Trasferta dell'intervento _NUM_ del _DATE_", [
                        '_NUM_' => $codice,
                        '_DATE_' => dateFormat($data),
                    ]);
                    $riga->um = 'km';
                    $riga->idintervento = $id_intervento;
                    $riga->id_iva = $id_iva;
                    $riga->prezzo_unitario = $viaggio->prezzo_km_unitario;
                    $riga->sconto_unitario = $viaggio->scontokm_unitario;
                    $riga->tipo_sconto = $viaggio->tipo_scontokm;
                    //$riga->setSconto($viaggio->scontokm_unitario, $viaggio->tipo_scontokm);

                    $riga->qta = $qta_trasferta;

                    $riga->save();
                }
            }
        }
    }
}
