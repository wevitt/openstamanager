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

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Components\Descrizione;
use Modules\Preventivi\Components\Riga;
use Modules\Preventivi\Components\Sconto;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');
        $idtipointervento = post('idtipointervento');
        $data_bozza = post('data_bozza');
        $id_sede = post('idsede');
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = TipoSessione::find($idtipointervento);

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome, $data_bozza, $id_sede, $id_segment);

        $preventivo->idstato = post('idstato');
        $preventivo->save();

        $id_record = $preventivo->id;

        $iva_predefinita = setting('Iva predefinita');

        $prc = $database->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.$preventivo->idpagamento)['prc'];

        $importo_spese_di_trasporto = ($anagrafica->spese_di_trasporto) ? $anagrafica->importo_spese_di_trasporto : 0;
        $riga = Riga::build($preventivo);
        $riga->descrizione = 'Spesa di trasporto';
        $riga->note = 'Spesa di trasporto';
        $riga->prezzo_unitario = $importo_spese_di_trasporto;
        $riga->idiva = $iva_predefinita;
        $riga->qta = intval(100 / $prc);
        $riga->is_spesa_trasporto = 1;
        $riga->setPrezzoUnitario($riga->prezzo_unitario, $riga->idiva);
        $riga->save();

        if ($anagrafica->spese_di_incasso) {
            $importo_spese_di_incasso = $anagrafica->importo_spese_di_incasso;
        } else {
            $id_pagamento = $anagrafica->idpagamento_vendite;
            if (!$id_pagamento) {
                $id_pagamento = setting('Tipo di pagamento predefinito');
            }
            $importo_spese_di_incasso = $database->fetchOne(
                'SELECT importo_spese_di_incasso FROM co_pagamenti WHERE id = '.$id_pagamento
            )['importo_spese_di_incasso'];
        }

        $riga = Riga::build($preventivo);
        $riga->descrizione = 'Spesa di incasso';
        $riga->note = 'Spesa di incasso';
        $riga->prezzo_unitario = $importo_spese_di_incasso;
        $riga->idiva = $iva_predefinita;
        $riga->qta = intval(100 / $prc);
        $riga->is_spesa_incasso = 1;
        $riga->setPrezzoUnitario($riga->prezzo_unitario, $riga->idiva);
        $riga->save();

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => 'Contratto '.$preventivo->numero.' del '.dateFormat($preventivo->data_bozza).' - '.$preventivo->nome]);
        }

        flash()->info(tr('Aggiunto preventivo numero _NUM_!', [
            '_NUM_' => $preventivo['numero'],
        ]));

        break;

    case 'update':
        if (isset($id_record)) {
            $preventivo->idstato = post('idstato');
            $preventivo->nome = post('nome');
            $preventivo->idanagrafica = post('idanagrafica');
            $preventivo->idsede = post('idsede');
            $preventivo->idagente = post('idagente');
            $preventivo->idreferente = post('idreferente');
            $preventivo->idpagamento = post('idpagamento');
            $preventivo->idporto = post('idporto');
            $preventivo->tempi_consegna = post('tempi_consegna');
            $preventivo->numero = post('numero');
            $preventivo->condizioni_fornitura = post('condizioni_fornitura');
            $preventivo->informazioniaggiuntive = post('informazioniaggiuntive');

            // Informazioni sulle date del documento
            $preventivo->data_bozza = post('data_bozza') ?: null;
            $preventivo->data_rifiuto = post('data_rifiuto') ?: null;

            // Dati relativi alla validità del documento
            $preventivo->validita = post('validita');
            $preventivo->tipo_validita = post('tipo_validita');
            $preventivo->data_accettazione = post('data_accettazione') ?: null;
            $preventivo->data_conclusione = post('data_conclusione') ?: null;

            $preventivo->esclusioni = post('esclusioni');
            $preventivo->garanzia = post('garanzia');
            $preventivo->descrizione = post('descrizione');
            $preventivo->id_documento_fe = post('id_documento_fe');
            $preventivo->num_item = post('num_item');
            $preventivo->codice_cig = post('codice_cig');
            $preventivo->codice_cup = post('codice_cup');
            $preventivo->idtipointervento = post('idtipointervento');
            $preventivo->idiva = post('idiva');
            $preventivo->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

            $preventivo->save();

            $anagrafica = Anagrafica::find($id_anagrafica);
            $iva_predefinita = setting('Iva predefinita');

            //update spese incasso/trasporto in base a idpagamento
            $righe = $preventivo->getRighe();

            $prc = $database->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.$preventivo->idpagamento)['prc'];

            if ($anagrafica->spese_di_incasso) {
                $importo_spese_di_incasso = $anagrafica->importo_spese_di_incasso;
            } else {
                $importo_spese_di_incasso = $database->fetchOne(
                    'SELECT importo_spese_di_incasso FROM co_pagamenti WHERE id = '.$preventivo->idpagamento
                )['importo_spese_di_incasso'];
            }

            $riga_spese_incasso = $righe->where('is_spesa_incasso', 1)->first();
            if (empty($riga_spese_incasso)) {
                $riga = Riga::build($preventivo);
                $riga->descrizione = 'Spesa di incasso';
                $riga->note = 'Spesa di incasso';
                $riga->prezzo_unitario = $importo_spese_di_incasso;
                $riga->idiva = $iva_predefinita;
                $riga->qta = intval(100 / $prc);
                $riga->is_spesa_incasso = 1;
                $riga->setPrezzoUnitario($riga->prezzo_unitario, $riga->idiva);
                $riga->save();
            } else {
                $riga_spese_incasso->qta = intval(100 / $prc);
                $riga_spese_incasso->setPrezzoUnitario($importo_spese_di_incasso, $riga_spese_incasso->idiva);
                $riga_spese_incasso->save();
            }

            $riga_spese_trasporto = $righe->where('is_spesa_trasporto', 1)->first();
            if (empty($riga_spese_trasporto)) {
                $importo_spese_di_trasporto = ($anagrafica->spese_di_trasporto) ? $anagrafica->importo_spese_di_trasporto : 0;
                $riga = Riga::build($preventivo);
                $riga->descrizione = 'Spesa di trasporto';
                $riga->note = 'Spesa di trasporto';
                $riga->prezzo_unitario = $importo_spese_di_trasporto;
                $riga->idiva = $iva_predefinita;
                $riga->qta = intval(100 / $prc);
                $riga->is_spesa_trasporto = 1;
                $riga->setPrezzoUnitario($riga->prezzo_unitario, $riga->idiva);
                $riga->save();
            } else {
                $riga_spese_trasporto->qta = intval(100 / $prc);
                $riga_spese_trasporto->setPrezzoUnitario($riga_spese_trasporto->prezzo_unitario, $riga_spese_trasporto->idiva);
                $riga_spese_trasporto->save();
            }

            flash()->info(tr('Preventivo modificato correttamente!'));
        }

        break;

    // Duplica preventivo
    case 'copy':
        // Copia del preventivo
        $new = $preventivo->replicate();
        $new->numero = Preventivo::getNextNumero(Carbon::now(), $new->id_segment);
        $new->data_bozza = Carbon::now();

        $stato_preventivo = Stato::where('descrizione', '=', 'Bozza')->first();
        $new->stato()->associate($stato_preventivo);

        $new->save();

        $new->master_revision = $new->id;
        $new->descrizione_revision = '';
        $new->numero_revision = 0;
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Preventivo duplicato correttamente!'));
        break;

    case 'addintervento':
        if (post('idintervento') !== null) {
            // Selezione costi da intervento
            $idintervento = post('idintervento');
            $rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE id='.prepare($idintervento));
            $costo_km = $rs[0]['prezzo_km_unitario'];
            $costo_orario = $rs[0]['prezzo_ore_unitario'];

            $dbo->update('in_interventi', [
                'id_preventivo' => $id_record,
            ], ['id' => $idintervento]);

            // Imposto il preventivo nello stato "In lavorazione" se inizio ad aggiungere interventi
            $dbo->query("UPDATE `co_preventivi` SET idstato=(SELECT `id` FROM `co_statipreventivi` WHERE `descrizione`='In lavorazione') WHERE `id`=".prepare($id_record));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $rs[0]['codice'],
            ]));
        }
        break;

    // Scollegamento intervento da preventivo
    case 'unlink':
        if (isset($_GET['idpreventivo']) && isset($_GET['idintervento'])) {
            $idintervento = get('idintervento');

            $dbo->update('in_interventi', [
                'id_preventivo' => null,
            ], ['id' => $idintervento]);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    // Eliminazione preventivo
    case 'delete':
        try {
            $preventivo->delete();

            flash()->info(tr('Preventivo eliminato!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'manage_barcode':
        foreach (post('qta') as $id_articolo => $qta) {
            if ($id_articolo == '-id-') {
                continue;
            }

            // Dati di input
            $sconto = post('sconto')[$id_articolo];
            $tipo_sconto = post('tipo_sconto')[$id_articolo];
            $prezzo_unitario = post('prezzo_unitario')[$id_articolo];
            $id_dettaglio_fornitore = post('id_dettaglio_fornitore')[$id_articolo];
            $id_iva = $originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita');

            // Creazione articolo
            $originale = ArticoloOriginale::find($id_articolo);
            $articolo = Articolo::build($preventivo, $originale);
            $articolo->id_dettaglio_fornitore = $id_dettaglio_fornitore ?: null;
            $articolo->confermato = setting('Conferma automaticamente le quantità nei preventivi');

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->setSconto($sconto, $tipo_sconto);
            $articolo->qta = $qta;

            $articolo->save();
        }

        flash()->info(tr('Articoli aggiunti!'));

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($preventivo, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;

            //check if the item have linked items
            $concatenati = $dbo->fetchArray('SELECT * FROM mg_articoli_concatenati WHERE id_articolo='.prepare($articolo->idarticolo));

            foreach ($concatenati as $concatenato) {
                $concatenato_originale = ArticoloOriginale::find($concatenato['id_articolo_concatenato']);
                $riga_concatenato = Articolo::build($preventivo, $concatenato_originale);

                try {
                    $riga_concatenato->qta = post('qta');
                } catch (UnexpectedValueException $e) {
                    flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
                }

                $riga_concatenato->confermato = 1;
                $riga_concatenato->setPrezzoUnitario($concatenato['prezzo'], $concatenato['idiva']);

                $riga_concatenato->save();
            }
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;
        $articolo->data_evasione = post('data_evasione') ?: null;
        $articolo->ora_evasione = post('ora_evasione') ?: null;
        $articolo->confermato = post('confermato') ?: 0;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));
        $articolo->setProvvigione(post('provvigione'), post('tipo_provvigione'));

        try {
            $articolo->qta = $qta;
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($preventivo);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->note = post('note');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($preventivo);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->um = post('um') ?: null;
        $riga->data_evasione = post('data_evasione') ?: null;
        $riga->ora_evasione = post('ora_evasione') ?: null;
        $riga->confermato = post('confermato') ?: 0;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));

        $riga->qta = $qta;

        $riga->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($preventivo);
        }

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Eliminazione riga
    case 'delete_riga':
        $id_righe = (array)post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);
            $riga->delete();

            $riga = null;
        }

        flash()->info(tr('Righe eliminate!'));

        break;

    // Duplicazione riga
    case 'copy_riga':
        $id_righe = (array)post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($preventivo);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

    case 'add_revision':
        // Rimozione flag default_revision dal record principale e dalle revisioni
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision = '.prepare($preventivo->master_revision));

        // Copia del preventivo
        $new = $preventivo->replicate();

        $stato_preventivo = Stato::where('descrizione', '=', 'Bozza')->first();
        $new->stato()->associate($stato_preventivo);

        $new->save();

        $new->default_revision = 1;
        $new->numero_revision = $new->ultima_revisione + 1;
        $new->descrizione_revision = post('descrizione');
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Aggiunta nuova revisione!'));
        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
        }

        break;

    case 'update_inline':
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);

        if (!empty($riga)) {
            $riga->qta = post('qta');
            $riga->setSconto(post('sconto'), post('tipo_sconto'));
            $riga->save();

            flash()->info(tr('Quantità aggiornata!'));
        }

        break;

    case 'edit-price':
        $righe = $post['righe'];

        foreach ($righe as $riga) {
            $dbo->query(
                'UPDATE co_righe_preventivi
                SET prezzo_unitario = '.$riga['price'].'
                WHERE id = '.$riga['id']
            );
        }

        flash()->info(tr('Prezzi aggiornati!'));

        break;

    case 'get_spesa_incasso':
        $idpagamento = post('idpagamento');
        $idanagrafica = post('idanagrafica');

        $anagrafica = Anagrafica::find($idanagrafica);
        $iva_predefinita = setting('Iva predefinita');
        $righe = $preventivo->getRighe();

        if ($anagrafica->spese_di_incasso) {
            $importo_spese_di_incasso = $anagrafica->importo_spese_di_incasso;
        } else {
            $importo_spese_di_incasso = $database->fetchOne(
                'SELECT importo_spese_di_incasso FROM co_pagamenti WHERE id = '.$idpagamento
            )['importo_spese_di_incasso'];
        }

        $preventivo->idpagamento = $idpagamento;
        $preventivo->save();

        $prc = $database->fetchOne('SELECT * FROM co_pagamenti WHERE id = '.$idpagamento)['prc'];

        $riga_spese_incasso = $righe->where('is_spesa_incasso', 1)->first();
        if (!empty($riga_spese_incasso)) {
            $riga_spese_incasso->qta = intval(100 / $prc);
            $riga_spese_incasso->setPrezzoUnitario($importo_spese_di_incasso, $riga_spese_incasso->idiva);
            $riga_spese_incasso->save();
        }

        break;

    case 'incrementa_riduci':
        $id_riga = post('id_riga');
        $value = post('value');
        $type = post('type');

        $righe = $preventivo->getRighe();
        $riga = $righe->where('id', $id_riga)->first();

        if ($type == 'iva') {
            $old_iva = floatval($database->fetchOne('SELECT * FROM co_righe_preventivi WHERE id = '.$id_riga)['iva']);
            //$old_prezzo_unitario = floatval($database->fetchOne('SELECT * FROM co_righe_preventivi WHERE id = '.$id_riga)['prezzo_unitario']);
            $old_iva_unitaria = floatval($database->fetchOne('SELECT * FROM co_righe_preventivi WHERE id = '.$id_riga)['iva_unitaria']);

            $iva = $old_iva + floatval(number_format($value, 3));
            //$prezzo_unitario = $old_prezzo_unitario + (floatval(number_format($value, 3)) / $riga->qta);
            $iva_unitaria = $old_iva_unitaria + (floatval(number_format($value, 3)) / $riga->qta);

            $database->query('UPDATE co_righe_preventivi SET iva = '.prepare(number_format($iva, 3)).' WHERE id = '.prepare($id_riga));
            //$database->query('UPDATE co_righe_preventivi SET prezzo_unitario = '.prepare(number_format($prezzo_unitario, 3)).' WHERE id = '.prepare($id_riga));
            $database->query('UPDATE co_righe_preventivi SET iva_unitaria = '.prepare(number_format($iva_unitaria, 3)).' WHERE id = '.prepare($id_riga));
        } else {
            $value = floatval($value) / $riga->qta;
            $prezzo_unitario = $riga->prezzo_unitario + $value;
            $riga->setPrezzoUnitario($prezzo_unitario, $riga->idiva);
            $riga->save();
        }

        break;
}
