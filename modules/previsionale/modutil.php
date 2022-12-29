<?php

include_once __DIR__.'/../../core.php';

use Util\Generator;

//Funzioni che restituisce tutti i mesi racchiusi nell'intervallo di data del filtro calendario
function get_mesi_ricorrenze()
{
    $mesi = [ '', tr('Gen'), tr('Feb'), tr('Mar'), tr('Apr'), tr('Mag'), tr('Giu'), tr('Lug'), tr('Ago'), tr('Set'), tr('Ott'), tr('Nov'), tr('Dic') ];
    $data_ricorrenza = $_SESSION['period_start'];
    $result = [];

    while ($data_ricorrenza <= $_SESSION['period_end']) {
        $m = intval(date("m", strtotime($data_ricorrenza)));
        $anno = date("y", strtotime($data_ricorrenza));
        $result[] = [
            'descrizione' => $mesi[$m].' '.$anno,
            'mese' => date("m", strtotime($data_ricorrenza)),
            'anno' => $anno,
        ];

        $data_ricorrenza = date("Y-m-d", strtotime('+1 MONTH '.$data_ricorrenza));
    }

    return $result;
}

//Funzione che calcola il codice progressivo dei previsionali
function get_next_codice()
{
    $maschera = "#";

    $ultimo = Generator::getPreviousFrom($maschera, 'bu_previsionale', 'codice');
    $codice = Generator::generate($maschera, $ultimo);

    return $codice;
}
