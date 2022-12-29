<?php

use Modules\VenditaBanco\Stampante;
use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

$ip_registratore = setting('Indirizzo IP registratore di cassa');
$porta_registratore = setting('Porta registratore di cassa');
$stampante_fiscale = new Stampante($ip_registratore, $porta_registratore);

$stampante_non_fiscale = new Stampante(setting('Indirizzo IP stampante termica'), setting('Porta stampante termica'), false);

if (isset($id_record)) {
    $documento = Vendita::find($id_record);
    $numero_righe = $documento->getRighe()->count();

    $is_pagato = $documento->isPagato();

    $record = $documento->toArray();

    $magazzino = $dbo->fetchOne("SELECT IF(idmagazzino=0, (SELECT CONCAT_WS(' - ', 'Sede legale' , citta, ' (', ragione_sociale,')') FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=".prepare($user->idanagrafica)."), (SELECT CONCAT_WS(' - ', nomesede, citta) FROM an_sedi WHERE an_sedi.id=vb_venditabanco.idmagazzino)) AS descrizione FROM vb_venditabanco WHERE vb_venditabanco.id=".prepare($id_record))['descrizione'];

    $categorie = $dbo->fetchArray('SELECT * FROM mg_categorie ORDER BY nome');
}
