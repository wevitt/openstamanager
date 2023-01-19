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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - permetti_movimento_a_zero
     * - idsede_partenza e idsede_destinazione
     * - dir
     * - idanagrafica
     */
    case 'articoli':
        $sedi_non_impostate = !isset($superselect['idsede_partenza']) && !isset($superselect['idsede_destinazione']);
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $usare_dettaglio_fornitore = $superselect['dir'] == 'uscita';
        $ricerca_codici_fornitore = $superselect['ricerca_codici_fornitore'];
        $usare_iva_anagrafica = $superselect['dir'] == 'entrata' && !empty($superselect['idanagrafica']);
        $solo_non_varianti = $superselect['solo_non_varianti'];
        $idagente = $superselect['idagente'];
        $id_listino = $superselect['id_listino'];

        $query = "SELECT
            DISTINCT mg_articoli.id,
            IF(`categoria`.`nome` IS NOT NULL, CONCAT(`categoria`.`nome`, IF(`sottocategoria`.`nome` IS NOT NULL, CONCAT(' (', `sottocategoria`.`nome`, ')'), '-')), '<i>".tr('Nessuna categoria')."</i>') AS optgroup,
            mg_articoli.barcode,

            mg_articoli.".($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita')." AS prezzo_vendita,
            mg_articoli.prezzo_vendita_ivato AS prezzo_vendita_ivato,
            mg_articoli.".($prezzi_ivati ? 'minimo_vendita_ivato' : 'minimo_vendita')." AS minimo_vendita,";

        // Informazioni relative al fornitore specificato dal documenti di acquisto
        if ($usare_dettaglio_fornitore) {
            $query .= '
            IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
            IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
            IFNULL(mg_fornitore_articolo.prezzo_acquisto, mg_articoli.prezzo_acquisto) AS prezzo_acquisto,
            IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima,
            mg_fornitore_articolo.id AS id_dettaglio_fornitore,';
        }
        // Informazioni dell'articolo per i documenti di vendita
        else {
            $query .= '
            mg_articoli.codice AS codice,
            mg_articoli.descrizione AS descrizione,
            mg_articoli.prezzo_acquisto AS prezzo_acquisto,
            0 AS qta_minima,
            NULL AS id_dettaglio_fornitore,';
        }

        if ($usare_iva_anagrafica) {
            $query .= '
            IFNULL(iva_anagrafica.id, IFNULL(iva_articolo.id, iva_predefinita.id)) AS idiva_vendita,
            IFNULL(iva_anagrafica.descrizione, IFNULL(iva_articolo.descrizione, iva_predefinita.descrizione)) AS iva_vendita,
            IFNULL(iva_anagrafica.percentuale, IFNULL(iva_articolo.percentuale, iva_predefinita.percentuale)) AS percentuale,';
        } else {
            $query .= '
            IFNULL(iva_articolo.id, iva_predefinita.id) AS idiva_vendita,
            IFNULL(iva_articolo.descrizione, iva_predefinita.descrizione) AS iva_vendita,
            IFNULL(iva_articolo.percentuale, iva_predefinita.percentuale) AS percentuale,';
        }

        if ($idagente) {
            $query .= '
            co_provvigioni.provvigione AS provvigione,
            co_provvigioni.tipo_provvigione AS tipo_provvigione,';
        }

        $query .= '
            round(mg_articoli.qta,'.setting('Cifre decimali per quantità').") AS qta,
            mg_articoli.um,
            mg_articoli.servizio,

            mg_articoli.idconto_vendita,
            mg_articoli.idconto_acquisto,
            categoria.`nome` AS categoria,
            sottocategoria.`nome` AS sottocategoria,
            righe.media_ponderata,

            CONCAT(conto_vendita_categoria .numero, '.', conto_vendita_sottocategoria.numero, ' ', conto_vendita_sottocategoria.descrizione) AS idconto_vendita_title,
            CONCAT(conto_acquisto_categoria .numero, '.', conto_acquisto_sottocategoria.numero, ' ', conto_acquisto_sottocategoria.descrizione) AS idconto_acquisto_title

        FROM mg_articoli
            LEFT JOIN `mg_categorie` AS categoria ON `categoria`.`id` = `mg_articoli`.`id_categoria`
            LEFT JOIN `mg_categorie` AS sottocategoria ON `sottocategoria`.`id` = `mg_articoli`.`id_sottocategoria`
            LEFT JOIN co_pianodeiconti3 AS conto_vendita_sottocategoria ON conto_vendita_sottocategoria.id=mg_articoli.idconto_vendita
                LEFT JOIN co_pianodeiconti2 AS conto_vendita_categoria ON conto_vendita_sottocategoria.idpianodeiconti2=conto_vendita_categoria.id
            LEFT JOIN co_pianodeiconti3 AS conto_acquisto_sottocategoria ON conto_acquisto_sottocategoria.id=mg_articoli.idconto_acquisto
                LEFT JOIN co_pianodeiconti2 AS conto_acquisto_categoria ON conto_acquisto_sottocategoria.idpianodeiconti2=conto_acquisto_categoria.id

            LEFT JOIN (SELECT co_righe_documenti.idarticolo AS id, (SUM((co_righe_documenti.prezzo_unitario-co_righe_documenti.sconto_unitario)*co_righe_documenti.qta)/SUM(co_righe_documenti.qta)) AS media_ponderata FROM co_righe_documenti
            LEFT JOIN co_documenti ON co_documenti.id=co_righe_documenti.iddocumento
            LEFT JOIN co_tipidocumento ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_tipidocumento.dir='uscita' GROUP BY co_righe_documenti.idarticolo) AS righe
            ON righe.id=mg_articoli.id
            LEFT JOIN co_iva AS iva_articolo ON iva_articolo.id = mg_articoli.idiva_vendita
            LEFT JOIN co_iva AS iva_predefinita ON iva_predefinita.id = (SELECT valore FROM zz_settings WHERE nome = 'Iva predefinita')";

        if ($usare_iva_anagrafica) {
            $query .= '
            LEFT JOIN co_iva AS iva_anagrafica ON iva_anagrafica.id = (SELECT idiva_vendite FROM an_anagrafiche WHERE idanagrafica = '.prepare($superselect['idanagrafica']).')';
        }

        if ($idagente) {
            $query .= '
            LEFT JOIN co_provvigioni ON co_provvigioni.idarticolo = mg_articoli.id AND co_provvigioni.idagente='.prepare($idagente);
        }

            $query .= '

            LEFT JOIN mg_fornitore_articolo ON mg_fornitore_articolo.id_articolo = mg_articoli.id AND mg_fornitore_articolo.deleted_at IS NULL AND mg_fornitore_articolo.id_fornitore = '.prepare($superselect['idanagrafica']);

        // Se c'è una sede settata, carico tutti gli articoli presenti in quella sede
        if (!$sedi_non_impostate) {
            $query .= '
            LEFT JOIN (SELECT idarticolo, idsede FROM mg_movimenti GROUP BY idarticolo) movimenti ON movimenti.idarticolo=mg_articoli.id
            LEFT JOIN an_sedi ON an_sedi.id = movimenti.idsede';
        }

        $query .= '
        |where|';

        // Se c'è una sede settata, carico tutti gli articoli presenti in quella sede
        if (!$sedi_non_impostate) {
            $query .= '
        GROUP BY
            mg_articoli.id';
        }

        $query .= '
        ORDER BY
            mg_articoli.id_categoria ASC,
            mg_articoli.id_sottocategoria ASC,
            mg_articoli.codice ASC,
            mg_articoli.descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'mg_articoli.id='.prepare($element);
        }

        $where[] = 'mg_articoli.attivo = 1';
        $where[] = 'mg_articoli.deleted_at IS NULL';

        if ($solo_non_varianti) {
            $where[] = 'mg_articoli.id_combinazione IS NULL';
        }

        if ($id_listino) {
            $where[] = 'mg_articoli.id NOT IN (SELECT id_articolo FROM mg_listini_articoli WHERE id_listino='.prepare($id_listino).')';
        }

        if (!empty($search)) {
            $search_fields[] = 'mg_articoli.descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.codice LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'mg_articoli.barcode LIKE '.prepare('%'.$search.'%');

            if ($usare_dettaglio_fornitore) {
                $search_fields[] = 'mg_fornitore_articolo.descrizione LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'mg_fornitore_articolo.codice_fornitore LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'mg_fornitore_articolo.barcode_fornitore LIKE '.prepare('%'.$search.'%');
            }

            if ($ricerca_codici_fornitore) {
                $search_fields[] = 'mg_articoli.id IN (SELECT mg_fornitore_articolo.id_articolo FROM mg_fornitore_articolo WHERE mg_fornitore_articolo.descrizione LIKE '.prepare('%'.$search.'%').')';
                $search_fields[] = 'mg_articoli.id IN (SELECT mg_fornitore_articolo.id_articolo FROM mg_fornitore_articolo WHERE mg_fornitore_articolo.codice_fornitore LIKE '.prepare('%'.$search.'%').')';
                $search_fields[] = 'mg_articoli.id IN (SELECT mg_fornitore_articolo.id_articolo FROM mg_fornitore_articolo WHERE mg_fornitore_articolo.barcode_fornitore LIKE '.prepare('%'.$search.'%').')';
            }
        }

        $data = AJAX::selectResults($query, $where, $filter, $search_fields, $limit, $custom);
        $rs = $data['results'];

        // Utilizzo dell'impostazione per disabilitare articoli con quantità <= 0
        $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita') ? true : $superselect['permetti_movimento_a_zero'];

        // Eventuali articoli disabilitati
        foreach ($rs as $k => $r) {
            // Lettura movimenti delle mie sedi
            $qta_sede = $dbo->fetchOne('SELECT SUM(mg_movimenti.qta) AS qta FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede WHERE mg_movimenti.idarticolo = '.prepare($r['id']).' AND idsede = '.prepare($superselect['idsede_partenza']))['qta'];

            $rs[$k] = array_merge($r, [
                'text' => $r['codice'].' - '.$r['descrizione'].' '.(!$r['servizio'] ? '('.Translator::numberToLocale($qta_sede).(!empty($r['um']) ? ' '.$r['um'] : '').')' : ''),
                'disabled' => $qta_sede <= 0 && !$permetti_movimenti_sotto_zero && !$r['servizio'],
            ]);
        }

        $results = [
            'results' => $rs,
            'recordsFiltered' => $data['recordsFiltered'],
        ];

        break;

    case 'categorie':
        $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY nome';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        $where[] = '`parent` IS NULL';

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        break;

    /*
     * Opzioni utilizzate:
     * - id_categoria
     */
    case 'sottocategorie':
        if (isset($superselect['id_categoria'])) {
            $query = 'SELECT id, nome AS descrizione FROM mg_categorie |where| ORDER BY nome';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_categoria']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'misure':
        $query = 'SELECT valore AS id, valore AS descrizione FROM mg_unitamisura |where| ORDER BY valore';

        foreach ($elements as $element) {
            $filter[] = 'valore='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'valore LIKE '.prepare('%'.$search.'%');
        }

        break;

    /*
     * Opzioni utilizzate:
     * - idanagrafica
     */
    case 'articoli_barcode':
        $id_anagrafica = filter('id_anagrafica'); // ID passato via URL in modo fisso
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

        if (!empty($search)) {
            $query = 'SELECT mg_articoli.*,
                mg_articoli.id,
                mg_articoli.qta,
                mg_articoli.um,
                mg_articoli.id,
                mg_articoli.id,
                IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
                IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
                IFNULL(mg_fornitore_articolo.prezzo_acquisto, mg_articoli.prezzo_acquisto) AS prezzo_acquisto,
                mg_articoli.'.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_vendita,
                mg_articoli.prezzo_vendita_ivato AS prezzo_vendita_ivato,
                IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima,
                mg_fornitore_articolo.id AS id_dettaglio_fornitore
            FROM mg_articoli
                LEFT JOIN mg_fornitore_articolo ON mg_fornitore_articolo.id_articolo = mg_articoli.id AND mg_fornitore_articolo.deleted_at IS NULL AND mg_fornitore_articolo.id_fornitore = '.prepare($id_anagrafica).'
            |where|';

            $where[] = 'mg_articoli.attivo = 1';
            $where[] = 'mg_articoli.deleted_at IS NULL';

            $search_fields[] = 'REPLACE(mg_articoli.codice, "/", "-") = ' . prepare($search);
            $search_fields[] = 'REPLACE(mg_articoli.barcode, "/", "-") = ' . prepare($search);
        }

        break;

    case 'fornitori-articolo':
        $query = 'SELECT an_anagrafiche.idanagrafica AS id, an_anagrafiche.ragione_sociale AS descrizione, (mg_prezzi_articoli.prezzo_unitario-(mg_prezzi_articoli.prezzo_unitario*mg_prezzi_articoli.sconto_percentuale)/100) AS prezzo_unitario FROM mg_prezzi_articoli LEFT JOIN an_anagrafiche ON mg_prezzi_articoli.id_anagrafica=an_anagrafiche.idanagrafica |where| ORDER BY an_anagrafiche.ragione_sociale';

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = 'dir="uscita"';
        $where[] = 'minimo IS NULL';
        $where[] = 'massimo IS NULL';
        $where[] = 'id_articolo='.prepare($superselect['id_articolo']);

        if (!empty($search)) {
            $search_fields[] = 'an_anagrafiche.ragione_sociale LIKE '.prepare('%'.$search.'%');
        }
        break;
}
