<?php

namespace Modules\VenditaBanco\Formati;

use Modules\VenditaBanco\Vendita;

class TXT extends Connettore
{
    public function printDocument(Vendita $documento, $codice_lotteria, $show_price = 1)
    {
        // Righe documento
        $rows = [];
        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            // Formattazione di descrizione secondo il formato
            $descrizione = $riga->descrizione;

            // Formattazione di quantità secondo il formato
            $qta = floatval($riga->qta);
            $decimals = floor($qta) != $qta ? 2 : 0;
            $quantita = number_format($riga->qta, $decimals, '.', '');

            $rows[] = $quantita.' x '.$descrizione;
        }

        // Costruzione del messaggio
        $message = $rows;

        // Messaggio di cortesia
        $messaggio_cortesia = '';
        $message[] = !empty($messaggio_cortesia) ? $messaggio_cortesia : '';

        // Spazio per taglio manuale
        $message[] = str_repeat("\n", 10);

        // Invio del messaggio
        parent::sendMessage($message);
    }
}
