<?php

namespace Modules\VenditaBanco\Formati;

use Carbon\Carbon;
use Exception;
use Modules\VenditaBanco\Vendita;

class XONXOFF extends Connettore
{
    /**
     * Invia il documento al registratore secondo il protocollo XON-XOFF.
     *
     * @source http://www.tditcompany.it/wp-content/uploads/2016/10/EPSON-XON-XOFF_Rev_4.8_FP90ii.pdf
     * @source http://www.neaitalia.it/download/temp/er/Comandi%20XON%20XOFF/Manuale%20Protocolli%20New.pdf
     *
     * @throws Exception
     */
    public function printDocument(Vendita $documento, $codice_lotteria, $show_price = 1)
    {
        if ($documento->totale < 0) {
            throw new Exception('Documento di vendita con totale negativo');
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga)  {
            if (empty($riga->id_reparto) && !$riga->isDescrizione()) {
                throw new Exception('Verificare che sia presente il reparto su tutte le righe');
            } 
        }

        if ($this->stampante->isFiscale()) {
            $this->fiscale($documento, $codice_lotteria);
        } else {
            $this->nonFiscale($documento, $show_price);
        }
    }

    protected function nonFiscale(Vendita $documento, $show_price)
    {
        // Costruzione del messaggio
        $message = [];
        $message[] = 'j';

        // Righe documento
        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            // Formattazione di descrizione secondo il formato
            $descrizione = substr(str_replace('"', "''", $riga->descrizione), 0, 32);

            // Formattazione di quantità secondo il formato
            $qta = floatval($riga->qta);
            $decimals = floor($qta) != $qta ? 2 : 0;
            $quantita = number_format($riga->qta, $decimals, '.', '');

            // Determinazione del prezzo effettivo
            $prezzo_unitario = ($riga->prezzo_unitario_ivato - $riga->sconto_unitario_ivato);
            $prezzo_formato = number_format($prezzo_unitario, 2, '.', '');

            if ($show_price) {
                $message[] = '"'.$descrizione.' ('.$quantita.'x'.$prezzo_formato.')"@';
            } else {
                $message[] = '"'.$descrizione.' ('.$quantita.'x)"@';
            }
        }

        // Messaggio di cortesia
        $messaggio_cortesia = $this->getMessaggioCortesia();
        (empty($messaggio_cortesia) ?: $message[] = '"'.$messaggio_cortesia.'"@40F');

        $message[] = 'J';

        // Invio del messaggio
        parent::sendMessage($message);
    }

    protected function getMessaggioCortesia()
    {
        return '';
    }

    protected function fiscale(Vendita $documento, $codice_lotteria)
    {
        $database = database();
        $documento->data_fiscale = Carbon::now();
        $documento->save();

        // Costruzione del messaggio
        $message = [];

        //Codice lotteria degli scontrini
        (empty($codice_lotteria) ? $message[] = '""@37F' : $message[] = '"'.$codice_lotteria.'"@37F');

        // Righe documento
        $sconti = [];
        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            $prezzo_unitario = $sconto_unitario = null;

            // Formattazione di descrizione secondo il formato
            $descrizione = substr(str_replace('"', "''", $riga->descrizione), 0, 32);

            // Formattazione di quantità secondo il formato
            $qta = floatval($riga->qta);
            $decimals = floor($qta) != $qta ? 2 : 0;
            $quantita = number_format($riga->qta, $decimals, '.', '');

            // Individuazione reparto della riga (1R, 2R, ...)
            $reparto = $database->fetchOne('SELECT `codice` FROM `vb_reparti` WHERE `id` = '.prepare($riga->id_reparto));

            // Gestione descrizioni
            if ($riga->isDescrizione()) {
                $message[] = '"'.$descrizione.'"@';
                continue;
            }
            // Gestione sconti/maggiorazioni
            elseif ($riga->isSconto()) {
                $sconto_unitario = $riga->sconto_unitario_ivato;

                // Maggiorazione: riga normale con totale impostato
                if ($sconto_unitario < 0) {
                    $prezzo_unitario = abs($sconto_unitario);
                    $sconto_unitario = 0;
                }
                // Sconto: riga specifica per il formato
                else {
                    $sconto_unitario_formato = number_format(abs($sconto_unitario * $riga->qta), 2, '', '');
                    $message[] = '='.$sconto_unitario_formato.'H4M';
                    continue;
                }
            }

            // Determinazione del prezzo effettivo
            $prezzo_unitario = isset($prezzo_unitario) ? $prezzo_unitario : $riga->prezzo_unitario_ivato;
            $sconto_unitario = isset($sconto_unitario) ? $sconto_unitario : $riga->sconto_unitario_ivato;
            if ($sconto_unitario < 0) {
                $prezzo_unitario -= $sconto_unitario;
                $sconto_unitario = 0;
            }

            // Fix per la gestione di righe con prezzo nullo: si impone prezzo e sconto a 0.01 per mostrare correttamente la riga
            // Nota: è normale che venga visualizzata una riga con Descrizione e una di Sconto
            if (empty($prezzo_unitario)) {
                $prezzo_unitario = $sconto_unitario = 0.01;
            }
            // Formattazione dei prezzi secondo il formato
            $prezzo_formato = number_format($prezzo_unitario, 2, '', '');
            $sconto_unitario_formato = number_format(abs($sconto_unitario * $riga->qta), 2, '', '');

            /*
            * Formato della singola riga:
            * - [H]: identificatore del campo prezzo/valore
            * - [*]: identificatore del campo quantità
            * - [.]: separatore decimale del campo quantità
            * - ["]: identificatore del campo descrizione
            * - [1R]: Reparto n.1
            *
            * Nota: la massima lunghezza del campo descrizione è di 22 caratteri; il campo descrizione può contenere caratteri ALFANUMERICI.
            */
            $row = '"'.$descrizione.'"'.$quantita.'*'.$prezzo_formato.'H'.$reparto['codice'];

            // Aggiunta dello sconto sulla riga sulla base del reparto
            if (!empty($sconto_unitario)) {
                $row .= $sconto_unitario_formato.'H3M';
            }

            $message[] = $row;
        }

        /*
         * Modalità di pagamento disponibili:
         * - [1T]: contanti
         * - [2T]: assegno
         * - [3T]: pos
         * - [4T]: visa
         * - [7T]: generico
         */
        $pagamento = $documento->pagamento->tipo_xon_xoff; // '1T'
        $message[] = $pagamento;

        //Campo descrittivo
        (empty($codice_lotteria) ?: $message[] = '"Lotteria: '.$codice_lotteria.'"@38F');

        // Messaggio di cortesia
        $messaggio_cortesia = $this->getMessaggioCortesia();
        (empty($messaggio_cortesia) ?: $message[] = '"'.$messaggio_cortesia.'"@40F');

        // Apertura del cassetto del registratore
        $message[] = 'a';

        // Invio del messaggio
        parent::sendMessage($message);
    }
}
