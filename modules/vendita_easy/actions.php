<?php

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Fattura;
use Modules\Fatture\Tipo;
//classi per creazione fattura
use Modules\VenditaBanco\Components\Articolo;
use Modules\VenditaBanco\Stato;
use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

$reparto_predefinito = setting('Reparto predefinito');
$easy = false;

switch (filter('op')) {
    // Creazione del documento di vendita
    case 'add':
        $documento = Vendita::build();
        $documento->save();

        //Magazzino
        $idmagazzino = $dbo->fetchOne('SELECT idsede FROM zz_user_sedi WHERE id_user='.prepare($user->id))['idsede'];
        $idmagazzino = ($idmagazzino ? $idmagazzino : 0);
        $documento->$idmagazzino;
        $documento->save();

        $id_record = $documento->id;

        flash()->info(tr('Nuova vendita al banco numero _NUM_ creata!', [
            '_NUM_' => $documento->numero,
        ]));

        $easy = true;

        break;

    case 'update':
        $documento->idpagamento = post('idpagamento');
        $documento->importo_pagato = post('importo_pagato');

        $result = true;
        $chiusura_documento = post('chiusura');
        if ($chiusura_documento == 1) {
            //Chiusa fiscale vendite aperte
            $vendite_aperte = $dbo->fetchArray('SELECT id FROM vb_venditabanco WHERE vb_venditabanco.idstato=1 AND deleted_at IS NULL AND vb_venditabanco.id NOT IN (SELECT vb_venditabanco_movimenti.idvendita FROM vb_venditabanco_movimenti)');
            foreach ($vendite_aperte as $idvendita) {
                $vendita = Vendita::find($idvendita['id']);
                if ($vendita->idpagamento) {
                    $stato_pagato = Stato::where('descrizione', 'Pagato')->first();
                    $vendita->stato()->associate($stato_pagato);
                    $vendita->save();
                } else {
                    flash()->warning(tr('Una o più vendite non sono state pagate!'));
                    $result = false;
                }
            }

            if ($result) {
                //Report FINANZIARIO + CHIUSURA FISCALE con azzeramento
                //2FReport FINANZIARIO + CHIUSURA FISCALE con azzeramento
                //8FReport FINANZIARIO + CHIUSURA FISCALE con azzeramento (uguale a 2F)

                $message = $message.'1F';

                $fp = stream_socket_client('tcp://'.setting('Indirizzo IP registratore di cassa').':'.setting('Porta registratore di cassa'), $errno, $errstr, 3);
                if (!$fp) {
                    array_push($_SESSION['errors'], "Errore durante l'invio al registratore di cassa: ".$errstr.' ('.$errno.')');
                } else {
                    fwrite($fp, $message, strlen($message));
                    fclose($fp);

                    array_push($_SESSION['infos'], 'Dati inviati al registratore di cassa!');
                }
            }

            flash()->info(tr('Chiusura fiscale completata!'));
        } else {
            try {
                $stato_aperto = Stato::where('descrizione', 'Aperto')->first();
                $documento->stato()->associate($stato_aperto);
                $documento->save();
                $message = tr('Vendita modificata!');
            } catch (Exception $e) {
                $result = false;
                $message = tr('Errore nel salvataggio delle informazioni: _MESSAGE_', [
                    '_MESSAGE_' => $e->getMessage(),
                ]);
            }

            echo json_encode([
                'result' => $result,
                'message' => $message,
            ]);
        }

        $easy = true;

        break;

    case 'add_articolo':
        $documento = Vendita::find($id_record);
        $idmagazzino = $documento->idmagazzino;
        $idarticolo = post('idarticolo');
        $idriga = post('idriga');
        $barcode = post('barcode');

        if (!empty($barcode) && empty($idarticolo) && empty($idriga)) {
            $articolo = $dbo->fetchOne('SELECT mg_articoli.id FROM mg_articoli INNER JOIN mg_movimenti ON mg_articoli.id = mg_movimenti.idarticolo WHERE mg_movimenti.idsede = '.prepare($idmagazzino).' AND mg_articoli.deleted_at IS NULL AND mg_articoli.barcode = '.prepare($barcode));

            $idarticolo = $articolo['id'];
        }

        // controllo se esiste già l'articolo nella vendita
        $rs = $dbo->fetchOne('SELECT id FROM vb_righe_venditabanco WHERE idarticolo='.prepare($idarticolo).' AND idvendita='.prepare($id_record));

        if (empty($rs) && empty($idriga)) {
            $originale = ArticoloOriginale::find($idarticolo);
            $articolo = Articolo::build($documento, $originale);

            $articolo->descrizione = $originale->descrizione;
            $articolo->um = $originale->um;
            $articolo->qta = 1;
            $articolo->costo_unitario = $originale->prezzo_acquisto;

            $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');

            $vendita = $originale->prezzo_vendita_ivato;
            $articolo->setPrezzoUnitario($vendita, $id_iva);
        } elseif (!empty($idriga)) {
            $articolo = Articolo::find($idriga);

            $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
            $articolo->setSconto(post('sconto'), post('tipo_sconto'));
            $articolo->costo_unitario = post('costo_unitario');
        } else {
            $articolo = Articolo::find($rs['id']);
            $articolo->qta = $articolo->qta + 1;
        }

        $articolo->id_reparto = post('id_reparto') ?: $reparto_predefinito;

        try {
            $articolo->save();

            $result = true;
            $message = tr('Articolo aggiunto!');
        } catch (Exception $e) {
            $result = false;
            $message = tr('Errore nel salvataggio delle informazioni: _MESSAGE_', [
                '_MESSAGE_' => $e->getMessage(),
            ]);
        }

        echo json_encode([
            'result' => $result,
            'message' => $message,
        ]);

        $easy = true;

        break;

    case 'reset_righe':
        $righe = $documento->getRighe();

        foreach ($righe as $riga) {
            $riga->delete();
        }

        if (empty($documento->getRighe())) {
            $dbo->query('DELETE FROM co_righe_documenti WHERE iddocumento='.prepare($documento->iddocumento));
            $dbo->query('DELETE FROM co_documenti WHERE id='.prepare($documento->iddocumento));
            $documento->iddocumento = null;
            $documento->save();
        }

        $easy = true;

        break;

    case 'cerca-barcode':
        $articolo = ArticoloOriginale::whereRaw('UPPER(barcode) = ?', [post('barcode')])
            ->first();

        echo json_encode([
            'id' => $articolo->id,
        ]);

        $easy = true;

        break;

    case 'fattura_vendita':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $idtipodocumento = post('idtipodocumento');
        $id_segment = post('id_segment');

        if ($dir == 'uscita') {
            $numero_esterno = post('numero_esterno');
        }

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($idtipodocumento);

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_segment, $numero_esterno);

        $calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $id_rivalsa_inps = post('id_rivalsa_inps') ?: null;
        $id_conto = post('id_conto');

        $righe = $documento->getRighe();

        foreach ($righe as $riga) {
            if ($riga->id) {
                $qta = $riga->qta;

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;
                $copia->ritenuta_contributi = $ritenuta_contributi;

                $copia->save();
            }
        }

        $documento->iddocumento = $fattura->id;
        $documento->save();

        $result = true;
        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        echo json_encode([
            'result' => $result,
            'message' => $message,
        ]);

        $easy = true;

        break;

    case 'apertura_cassetto':
        $message = $message.'a';

        $fp = stream_socket_client('tcp://'.setting('Indirizzo IP registratore di cassa').':'.setting('Porta registratore di cassa'), $errno, $errstr, 3);
        if (!$fp) {
            array_push($_SESSION['errors'], "Errore durante l'invio al registratore di cassa: ".$errstr.' ('.$errno.')');
        } else {
            fwrite($fp, $message, strlen($message));
            fclose($fp);

            //array_push($_SESSION['infos'], "Dati inviati al registratore di cassa!");
            //flash()->info(tr('Dati inviati al registratore di cassa!'));
        }

        $easy = true;

        break;

}

if( empty($easy) ){
    include_once __DIR__.'/../vendita_banco/actions.php';
}