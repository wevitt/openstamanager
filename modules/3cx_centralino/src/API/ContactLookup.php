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
use Centralino3CX\Chiamata;
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;

class ContactLookup extends Resource implements CreateInterface
{
    public function create($request)
    {
        $numero = Chiamata::filtraNumero($request['Number']);
        $in_entrata = $request['CallDirection'] != 'Outbound';

        // Aggiorna chiamate più vecchie di un giorno come senza risposta
        $this->fixSenzaRisposta();

        // Registrazione lookup come inizio chiamata
        $chiamata = Chiamata::build($numero, $in_entrata);
        $chiamata->numero_lookup = $request['Number'];
        $chiamata->save();

        // Invio notifiche Push per chiamate in entrata
        if ($in_entrata) {
            $this->processaNotifica($request, $chiamata);
        }

        return $this->response($chiamata);
    }

    /**
     * Predispone le informazioni per l'interpretazione da parte del centralino 3CX.
     *
     * @param $chiamata
     *
     * @return array|null[]
     */
    protected function response($chiamata)
    {
        $chiamante = $chiamata->trovaNumero();
        $modulo_anagrafiche = Module::pool('Anagrafiche');

        // Informazioni per le anagrafiche
        if ($chiamante instanceof Anagrafica) {
            $anagrafica = $chiamante;

            return [
                'id' => $anagrafica->id,
                'nome' => $anagrafica->nome,
                'cognome' => $anagrafica->cognome,
                'ragione_sociale' => $anagrafica->ragione_sociale,
                'email' => $anagrafica->email,
                'telefono' => $anagrafica->telefono,
                'cellulare' => $anagrafica->cellulare,
                'url' => base_url().'/controller.php?id_module='.$modulo_anagrafiche->id.'&id_record='.$anagrafica->id,
            ];
        } elseif ($chiamante instanceof Sede) {
            $anagrafica = $chiamante->anagrafica;
            $sede = $chiamante;

            return [
                'id' => $anagrafica->id,
                'nome' => $sede->nomesede,
                'cognome' => $anagrafica->ragione_sociale,
                'ragione_sociale' => $anagrafica->ragione_sociale.' ('.$sede->nomesede.')',
                'email' => $sede->email,
                'telefono' => $sede->telefono,
                'cellulare' => $sede->cellulare,
                'url' => base_url().'/controller.php?id_module='.$modulo_anagrafiche->id.'&id_record='.$anagrafica->id,
            ];
        } elseif ($chiamante instanceof Referente) {
            $anagrafica = $chiamante->anagrafica;
            $referente = $chiamante;

            return [
                'id' => $anagrafica->id,
                'nome' => $referente->nome,
                'cognome' => $anagrafica->ragione_sociale,
                'ragione_sociale' => $referente->nome.' ('.$anagrafica->ragione_sociale.')',
                'email' => $anagrafica->email,
                'telefono' => $referente->telefono,
                'cellulare' => $anagrafica->cellulare,
                'url' => base_url().'/controller.php?id_module='.$modulo_anagrafiche->id.'&id_record='.$anagrafica->id,
            ];
        }

        // Informazioni per contatto non trovato
        // Fix per call journaling di 3CX non abilitato per contatti non trovati
        return [
            'id' => 0,
            'nome' => '',
            'cognome' => '',
            'ragione_sociale' => $chiamata->numero,
            'email' => '',
            'telefono' => $chiamata->numero,
            'cellulare' => $chiamata->numero,
            'url' => '',
        ];
    }

    /**
     * Corregge i dati per eventuali chiamate senza risposta ancora da aggiornare di conseguenza.
     */
    protected function fixSenzaRisposta()
    {
        $database = database();

        $database->query("UPDATE 3cx_chiamate SET oggetto = CONCAT(oggetto, ' [senza risposta]'), fine = inizio, is_gestito = 1 WHERE id_tecnico IS NULL AND created_at <= NOW() - INTERVAL 12 HOUR");
    }

    /**
     * Richiesta HTTP fire-and-forget.
     *
     * @source https://cwhite.me/blog/fire-and-forget-http-requests-in-php
     */
    protected function processaNotifica($request, $chiamata)
    {
        $endpoint = base_url().'/api/';
        $postData = json_encode([
            'token' => $request['token'],
            'version' => '3cx',
            'resource' => 'call-notification',
            'id' => $chiamata->id,
        ]);

        // Gestione richiesta tramite exec diretto
        exec("curl -X POST -d '".$postData."' ".$endpoint."  > /dev/null &");

        return;

        $endpointParts = parse_url($endpoint);
        $endpointParts['path'] = $endpointParts['path'] ?: '/';
        $endpointParts['port'] = $endpointParts['port'] ?: ($endpointParts['scheme'] === 'https' ? 443 : 80);

        $contentLength = strlen($postData);

        $request = "POST {$endpointParts['path']} HTTP/1.1\r\n";
        $request .= "Host: {$endpointParts['host']}\r\n";
        $request .= "User-Agent: OpenSTAManager API v1\r\n";
        $request .= "Authorization: Bearer api_key\r\n";
        $request .= "Content-Length: {$contentLength}\r\n";
        $request .= "Content-Type: application/json\r\n\r\n";
        $request .= $postData;

        $prefix = substr($endpoint, 0, 8) === 'https://' ? 'tls://' : '';

        $socket = fsockopen($prefix.$endpointParts['host'], $endpointParts['port']);
        fwrite($socket, $request);
        fclose($socket);
    }
}
