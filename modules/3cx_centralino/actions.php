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

use Centralino3CX\Chiamata;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\TipiIntervento\Tipo as TipoSessione;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    /*
     * Azione per l'aggiornamento delle informazioni modificabili della chiamata.
     */
    case 'add':
        $numero = post('numero');
        $chiamata = Chiamata::build($numero, true);
        $chiamata->numero_lookup = $numero;
        $chiamata->save();

        $id_record = $chiamata->id;

        break;

    /*
     * Azione per l'aggiornamento delle informazioni modificabili della chiamata.
     */
    case 'update':
        $chiamata->descrizione = post('descrizione');
        $chiamata->is_gestito = post('is_gestito');
        $chiamata->save();

        break;

    /*
     * Azione per l'associazione a un intervento esistente, con aggiunta della sessione di lavoro della chiamata.
     */
    case 'associa_intervento':
        $id_intervento = filter('id_intervento');
        $intervento = Intervento::find($id_intervento);

        $codice_tipo = 'CALL';
        $tipo = TipoSessione::where('codice', '=', $codice_tipo)->first();

        // Creazione sessione dell'intervento
        $sessione = Sessione::build($intervento, $chiamata->tecnico, $chiamata->inizio, $chiamata->fine);
        $sessione->setTipo($tipo->id, true);
        $sessione->save();

        // Salvataggio associazione
        $chiamata->intervento()->associate($intervento);
        $chiamata->save();

        break;

    /*
     * Azione per l'associazione diretta all'ultimo intervento (appena creato secondo il pulsante "Crea intervento") disponibile per l'anagrafica della chiamata.
     */
    case 'associa_ultimo_intervento':
        $anagrafica = $chiamata->anagrafica;
        $intervento = Intervento::where('idanagrafica', '=', $anagrafica->id)
            ->latest()->first();

        // Salvataggio associazione
        $chiamata->intervento()->associate($intervento);
        $chiamata->save();

        echo json_encode([
            'id_intervento' => $intervento->id,
        ]);
        break;

    /*
     * Azione per la ricerca e il completamento dell'associazione con l'anagrafica della chiamata.
     */
    case 'ricerca':
        $collegamento = $chiamata->trovaNumero();
        $chiamata->associaCollegamento($collegamento);
        $chiamata->save();

        break;
}
