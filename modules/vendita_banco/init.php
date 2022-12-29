<?php

use Modules\VenditaBanco\Stampante;
use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

$ip_registratore = setting('Indirizzo IP registratore di cassa');
$porta_registratore = setting('Porta registratore di cassa');
$stampante_fiscale = new Stampante($ip_registratore, $porta_registratore);
$reparto_predefinito = setting('Reparto predefinito');

$stampante_non_fiscale = new Stampante(setting('Indirizzo IP stampante termica'), setting('Porta stampante termica'), false);

if (isset($id_record)) {
    $documento = Vendita::find($id_record);
    $numero_righe = $documento->getRighe()->count();

    $is_pagato = $documento->isPagato();

    $record = $documento->toArray();
}
