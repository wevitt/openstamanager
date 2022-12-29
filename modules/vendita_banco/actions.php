<?php

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\VenditaBanco\Components\Articolo;
use Modules\VenditaBanco\Components\Descrizione;
use Modules\VenditaBanco\Components\Riga;
use Modules\VenditaBanco\Components\Sconto;
use Modules\VenditaBanco\Formati\TXT;
use Modules\VenditaBanco\Formati\XONXOFF;
use Modules\VenditaBanco\Stato;
use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    // Creazione del documento di vendita
    case 'add':
        $documento = Vendita::build();
        $documento->save();

        $id_record = $documento->id;

        flash()->info(tr('Nuova vendita al banco numero _NUM_ creata!', [
            '_NUM_' => $documento->numero,
        ]));
        break;

    // Aggiornamento del documento di vendita
    case 'update':
        // Aggiornamento del magazzino di origine solo se non sono presenti righe
        if ($numero_righe == 0) {
            $documento->idmagazzino = post('idmagazzino');
        }

        $documento->note = post('note');
        $documento->idpagamento = post('idpagamento');
        $documento->importo_pagato = post('importo_pagato');
        $documento->idanagrafica = post('idanagrafica');
        $documento->save();

        // Aggiornamento stato a Pagato
        $chiusura_documento = post('closed');
        if (!empty($chiusura_documento)) {
            if (post('idpagamento')) {
                $stato_pagato = Stato::where('descrizione', 'Pagato')->first();
                $documento->stato()->associate($stato_pagato);
            }
        } else {
            $stato_aperto = Stato::where('descrizione', 'Aperto')->first();
            $documento->stato()->associate($stato_aperto);
        }

        $documento->save();

        flash()->info(tr('Vendita al banco modificata correttamente'));
        break;

    // Eliminazione del documento di vendita
    case 'delete':
        $documento->delete();

        flash()->info(tr('Vendita eliminata!'));
        break;

    // Aumenta la quantità della riga di 1
    case 'incrementa_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $documento->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->qta = $riga->qta + 1;
            $riga->save();
        }

        break;

    // Riduce la quantità della riga di 1
    case 'decrementa_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $documento->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->qta = $riga->qta - 1;
            $riga->save();

            if ($riga->qta <= 0) {
                $riga->delete();
            }
        }

        break;

    case 'aggiorna-sconto':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $documento->getRiga($type, $id_riga);

        if (!empty($riga) && ($riga->isRiga() || $riga->isArticolo())) {
            $riga->setSconto(post('sconto'), post('tipo_sconto'));
            $riga->save();
        }

        break;

    case 'aggiorna-reparto':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $documento->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->id_reparto = post('id_reparto') ?: null;
            $riga->save();
        }

        break;

    // Aggiornamento personalizzato per gli articoli
    case 'update_articolo':
        $articolo = Articolo::find(post('idriga'));

        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));
        $articolo->descrizione = post('descrizione');
        $articolo->costo_unitario = post('costo_unitario');
        $articolo->id_reparto = post('id_reparto') ?: ($articolo->id_reparto ?: $reparto_predefinito);

        $articolo->save();

        flash()->info(tr('Riga modificata!'));

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($documento);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        $riga->costo_unitario = post('costo_unitario');
        $riga->id_reparto = post('id_reparto') ?: ($riga->id_reparto ?: $reparto_predefinito);

        $riga->qta = $qta;

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($documento);
        }

        $sconto->descrizione = post('descrizione');
        $sconto_unitario = post('sconto_unitario');
        $sconto_percentuale = post('sconto_percentuale');

        if (!empty($sconto_percentuale)) {
            $sconto_unitario = $documento->totale * $sconto_percentuale / 100;
        }

        $sconto->setScontoUnitario($sconto_unitario, post('idiva'));
        $sconto->id_reparto = post('id_reparto') ?: ($sconto->id_reparto ?: $reparto_predefinito);

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($documento);
        }

        $riga->descrizione = post('descrizione');
        $riga->id_reparto = post('id_reparto') ?: ($riga->id_reparto ?: $reparto_predefinito);

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Eliminazione riga
    case 'delete_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $documento->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->delete();

            flash()->info(tr('Riga eliminata!'));
        }

        break;

    case 'stampa':
        $codice_lotteria = filter('codice_lotteria');

        $stampante = filter('fiscale') ? $stampante_fiscale : $stampante_non_fiscale;

        // Ricerca del formato richiesto
        $formato = null;
        $formato_richiesto = filter('formato');
        if ($formato_richiesto == 'xonxoff') {
            $stampante = $stampante_fiscale;
            $stampante->setFiscale(filter('fiscale') ? 1 : 0);

            $formato = XONXOFF::class;
        } elseif ($formato_richiesto == 'txt') {
            $formato = TXT::class;
        }

        if (empty($formato)) {
            echo json_encode([
                'result' => 404,
                'message' => tr('Formato non disponibile'),
            ]);

            return;
        }

        try {
            $gestore = new $formato($stampante);

            if (!empty($codice_lotteria)) {
                $gestore->printDocument($documento, $codice_lotteria, filter('show_price'));
            } else {
                $gestore->printDocument($documento, '', filter('show_price'));
            }

            $check = $dbo->fetchOne("SELECT id FROM vb_righe_venditabanco WHERE (id_reparto IS NULL OR id_reparto=0) AND idvendita=".prepare($id_record));
            if( empty($check) ){
                $result = true;
                $message = tr('Dati inviati al registratore di cassa!');
            }else{
                $result = False;
                $message = tr('Una o più righe del documento risultano prive di reparto selezionato!');
            }

        } catch (Exception $e) {
            $result = false;

            $message = tr("Errore durante l'invio al registratore di cassa: _MESSAGE_", [
                '_MESSAGE_' => $e->getMessage(),
            ]);
        }

        echo json_encode([
            'result' => $result,
            'message' => $message,
        ]);

        break;

    case 'add_articolo':
        $id_articolo = post('id_articolo');
        $codice = post('codice');
        $barcode = post('barcode');
        $idmagazzino = post('idmagazzino') ?: 0;

        if (!empty($id_articolo)) {
            if (setting('Gestisci articoli sottoscorta')) {
                $articolo = $dbo->fetchOne('SELECT mg_articoli.id FROM mg_articoli WHERE mg_articoli.deleted_at IS NULL AND mg_articoli.id = '.prepare($id_articolo));
            } else {
                $articolo = $dbo->fetchOne('SELECT mg_articoli.id FROM mg_articoli INNER JOIN mg_movimenti ON mg_articoli.id = mg_movimenti.idarticolo WHERE mg_movimenti.idsede= 0 AND mg_articoli.deleted_at IS NULL AND mg_articoli.id = '.prepare($id_articolo));
            }
        } elseif (!empty($codice)) {
            $articolo = $dbo->fetchOne('SELECT mg_articoli.id FROM mg_articoli INNER JOIN mg_movimenti ON mg_articoli.id = mg_movimenti.idarticolo WHERE mg_movimenti.idsede= '.prepare($idmagazzino).' AND mg_articoli.deleted_at IS NULL AND mg_articoli.codice = '.prepare(post('codice')));
        } elseif (!empty($barcode)) {
            $articolo = $dbo->fetchOne('SELECT mg_articoli.id FROM mg_articoli INNER JOIN mg_movimenti ON mg_articoli.id = mg_movimenti.idarticolo WHERE mg_movimenti.idsede = '.prepare($idmagazzino).' AND mg_articoli.deleted_at IS NULL AND mg_articoli.barcode = '.prepare($barcode));
        }

        if (!empty($articolo['id'])) {
            $originale = ArticoloOriginale::find($articolo['id']);
            $articolo = Articolo::build($documento, $originale);

            $qta = 1;

            $articolo->descrizione = $originale->descrizione;
            $articolo->um = $originale->um;
            $articolo->qta = 1;
            $articolo->costo_unitario = $originale->prezzo_acquisto;

            $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');

            $vendita = $originale->prezzo_vendita_ivato;
            $articolo->setPrezzoUnitario($vendita, $id_iva);
            $articolo->id_reparto = $originale->id_reparto ?: $reparto_predefinito;

            $articolo->save();

            if (!filter('ajax')) {
                flash()->info(tr('Articolo aggiunto'));
            }

            $highlight = $articolo['id'];

            // highlight
            $_SESSION['barcodes'] = [
                $highlight,
                post('op'),
            ];
        } else {
            if (!filter('ajax')) {
                flash()->warning(tr('Nessun articolo corrispondente a magazzino'));
            } else {
                $response['error'] = tr('Nessun articolo corrispondente a magazzino');
                echo json_encode($response);
            }

            // highlight input barcode
            $_SESSION['b-warnings'] = [
                $_POST['barcode'],
                post('op'),
            ];
        }

        break;
}
