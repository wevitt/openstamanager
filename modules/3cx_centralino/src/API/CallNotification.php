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
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Models\Module;
use Models\User;
use Modules\Anagrafiche\Anagrafica;

class CallNotification extends Resource implements CreateInterface
{
    public function create($request)
    {
        // Attesa di secondi per verificare se la chiamata è stata completata
        sleep(2);

        $chiamata = Chiamata::find($request['id']);

        // Invio notifiche Push per chiamate in entrata
        if ($chiamata->in_entrata && empty($chiamata->fine)) {
            $notifica = $this->buildNotification($chiamata);
            $this->sendNotification($notifica);
        }

        return [];
    }

    /**
     * Restituisce le chiavi utilizzate per la comunicazione tramite notifiche.
     *
     * @return array
     */
    public static function getKeys()
    {
        return [
            'public' => file_get_contents(__DIR__.'/../../keys/public_key.txt'),
            'private' => file_get_contents(__DIR__.'/../../keys/private_key.txt'),
        ];
    }

    /**
     * Genera le informazioni necessarie per la notifica della chiamata in entrata.
     *
     * @param $chiamata
     *
     * @return array
     */
    protected function buildNotification($chiamata)
    {
        $modulo_centralino = Module::pool('Centralino 3CX');
        $modulo_anagrafiche = Module::pool('Anagrafiche');

        $anagrafica = $chiamata->anagrafica;

        // Informazioni visibili sull'anagrafica
        $dati_anagrafica = null;
        if (!empty($anagrafica)) {
            $dati_anagrafica = [
                'ragione_sociale' => $anagrafica->ragione_sociale,
            ];
        }

        // Url predefiniti
        $url = [
            'info' => base_url().'/editor.php?id_module='.$modulo_centralino->id.'&id_record='.$chiamata->id,
            'anagrafica' => base_url().'/editor.php?id_module='.$modulo_anagrafiche->id.'&id_record='.$chiamata->id_anagrafica,
        ];

        $content = [
            'id' => $chiamata->id,
            'timestamp' => $chiamata->created_at->toIso8601String(),
            'numero' => $chiamata->numero,
            'anagrafica' => $dati_anagrafica,
            'url' => [
                'info' => $url['info'],
                'anagrafica' => $url['anagrafica'],
                'predefinito' => !empty($anagrafica) && $anagrafica->isTipo('Cliente') ? $url['info'] : $url['anagrafica'],
            ],
        ];

        return $content;
    }

    /**
     * Invia una specifica notifica a tutti i dispositivi registrati.
     *
     * @param $content
     *
     * @throws \ErrorException
     */
    protected function sendNotification($content)
    {
        $defaultOptions = [
            'TTL' => 10, // seconds
        ];

        // Gestore notifiche
        $keys = self::getKeys();
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'Chiamate in Ingresso', // can be a mailto: or your website address
                'publicKey' => $keys['public'],
                'privateKey' => $keys['private'],
            ],
        ], $defaultOptions);
        $webPush->setDefaultOptions($defaultOptions);

        // Invio notifiche
        $database = database();
        $endpoints = $database->fetchArray('SELECT * FROM 3cx_push');
        foreach ($endpoints as $endpoint) {
            /*$utente = User::find($endpoint['id_utente']);
            $moduli_disponibili = $utente->modules()->get();

            $centralino_disponibile = $moduli_disponibili->search(function ($module){
                return $module->name == 'Centralino 3CX';
            }) !== false;

            $anagrafiche_disponibile = $moduli_disponibili->search(function ($module){
                return $module->name == 'Anagrafiche';
            }) !== false;

            if (!$utente->is_admin && (!$centralino_disponibile || !$anagrafiche_disponibile)) {

            }*/

            $subscription = json_decode($endpoint['params'], true);

            $webPush->queueNotification(
                Subscription::create($subscription),
                json_encode($content)
            );
        }

        // Log delle notifiche
        foreach ($webPush->flush() as $id => $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if (!$report->isSuccess()) {
                $database->query('DELETE FROM 3cx_push WHERE id = :id', [
                    'id' => $endpoints[$id]['id'],
                ]);
            } else {
                //echo "[v] Message sent successfully for subscription {$endpoint}.<br>";
            }
        }
    }
}
