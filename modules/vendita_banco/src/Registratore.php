<?php

namespace Modules\VenditaBanco;

use Carbon\Carbon;
use Exception;

class Registratore
{
    protected static $ip;
    protected static $port;

    /**
     * @return string
     */
    public static function getIP()
    {
        if (!isset(static::$ip)) {
            static::$ip = setting('Indirizzo IP registratore di cassa');
        }

        return static::$ip;
    }

    /**
     * @return string
     */
    public static function getPort()
    {
        if (!isset(static::$port)) {
            static::$port = setting('Porta registratore di cassa');
        }

        return static::$port;
    }

    /**
     * @return bool
     */
    public static function isConfigured()
    {
        return !empty(static::getIP());
    }

    /**
     * Invia il documento al registratore secondo il protocollo XON-XOFF.
     *
     * @source http://www.tditcompany.it/wp-content/uploads/2016/10/EPSON-XON-XOFF_Rev_4.8_FP90ii.pdf
     * @source http://www.neaitalia.it/download/temp/er/Comandi%20XON%20XOFF/Manuale%20Protocolli%20New.pdf
     *
     * @throws Exception
     */
    public static function printDocument(Vendita $documento, $is_fiscale = 1)
    {
        if (!static::isConfigured()) {
            throw new Exception('Registratore non configurato');
        }

        $documento->data_fiscale = Carbon::now();
        $documento->save();

        // Righe documento
        $rows = [];
        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            $descrizione = str_replace('"', "''", $riga->descrizione);
            $quantita = number_format($riga->qta, 0, '', '');

            $totale_singolo = ($riga->prezzo_unitario_ivato - $riga->sconto_unitario_ivato);
            $prezzo = number_format($totale_singolo, 2, '', '');

            $reparto = $riga->aliquota->tipo_xon_xoff; // '1R'

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
            $rows[] = '"'.substr($descrizione, 0, 22).'"'.$quantita.'*'.$prezzo.'H'.$reparto."\n";
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
        ($is_fiscale ?:$message = "j\n");
        $message = $message.implode('', $rows).$pagamento."\n";

        // Azzera la stampante
        ($is_fiscale ?:$message = $message."J\n");
        $message = $message."K\n";

        // Apertura del cassetto del registratore
        $message = $message."a\n";

        // Apertura della connessione TCP
        $fp = stream_socket_client('tcp://'.static::getIP().':'.static::getPort(), $errno, $errstr, 3);
        if (!$fp) {
            throw new Exception($errstr.' ('.$errno.')');
        }

        // Invio del messaggio e chiusura della connessione
        fwrite($fp, $message, strlen($message));
        fclose($fp);
    }
}
