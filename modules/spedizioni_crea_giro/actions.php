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

switch (filter('op')) {
    case 'add':
        $nome_percorso = filter('nome_percorso');
        $note = filter('note');
        $punto_di_partenza = filter('punto_di_partenza');
        $data_di_partenza = filter('data_di_partenza');
        $orario_di_partenza = filter('orario_di_partenza');
        $tempo_totale = filter('tempo_totale');
        $km_totali = filter('km_totali');
        $percorso = filter('percorso');

        $id_consegna = filter('id');
        $numero_documento = filter('numero_documento');
        $tipo_consegna = filter('tipo_consegna');
        $ragione_sociale = filter('ragione_sociale');
        $indirizzo = filter('indirizzo');
        $citta = filter('citta');
        $cap = filter('cap');
        $provincia = filter('provincia');
        $tempo = filter('tempo');
        $arrivo = filter('arrivo');
        $km = filter('km');
        $colli = filter('colli');
        $ord = filter('ord');

        if (isset($nome_percorso)) {
            if ($dbo->fetchNum('SELECT * FROM `sp_spedizioni` WHERE `nome` = ' . prepare($nome)) == 0) {
                $dbo->query(
                    'INSERT INTO `sp_spedizioni`
                    (`nome_percorso`, `note`, `punto_di_partenza`, `data_di_partenza`, `orario_di_partenza`, `tempo_totale`,
                    `km_totali`, `percorso`)
                    VALUES (' . prepare($nome_percorso) . ', ' . prepare($note) . ', ' .prepare($punto_di_partenza) .
                    ', ' . prepare($data_di_partenza) . ', ' . prepare($orario_di_partenza) . ', ' . prepare($tempo_totale) .
                    ', ' . prepare($km_totali) . ', ' . prepare($percorso) . ')'
                );

                $id_record = $dbo->lastInsertedID();

                for ($i=0; $i<count($id_consegna)-1; $i++) {
                    $dbo->query(
                        'INSERT INTO `sp_spedizioni_dettaglio`
                        (`id_spedizione`, `id_consegna`,`ragione_sociale`, `numero_documento`, `colli`, `indirizzo`, `citta`, `cap`, `provincia`,
                        `tempo`, `orario_di_arrivo`, `km_percorsi`, `ordinamento`, `tipo_consegna`)
                        VALUES (' . prepare($id_record) . ', ' . prepare($id_consegna[$i]) . ', ' . prepare($ragione_sociale[$i]) .
                        ', ' . prepare($numero_documento[$i]) . ', ' . prepare($colli[$i]) . ', ' . prepare($indirizzo[$i]) .
                        ', ' . prepare($citta[$i]) . ', ' . prepare($cap[$i]) . ', ' . prepare($provincia[$i]) .
                        ', ' . prepare($tempo[$i]) . ', ' . prepare($arrivo[$i]) . ', ' . prepare($km[$i]) . ', ' .
                        prepare($ord[$i]) . ', ' . prepare($tipo_consegna[$i]) . ')'
                    );
                }

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $nome]);
                }

                flash()->info(tr('Aggiunta nuova relazione _NAME_', [
                    '_NAME_' => $nome,
                ]));
            } else {
                flash()->error(tr("E' già presente una relazione di _NAME_.", [
                    '_NAME_' => $nome,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

        case 'add-mio-percorso':
            $nome_percorso = filter('nome_percorso');
            $note = filter('note');
            $punto_di_partenza = filter('punto_di_partenza');

            $id_consegna = filter('id');
            $numero_documento = filter('numero_documento');
            $tipo_consegna = filter('tipo_consegna');
            $ragione_sociale = filter('ragione_sociale');
            $indirizzo = filter('indirizzo');
            $citta = filter('citta');
            $cap = filter('cap');
            $provincia = filter('provincia');
            $colli = filter('colli');

            if (isset($nome_percorso)) {
                if ($dbo->fetchNum('SELECT * FROM `sp_spedizioni` WHERE `nome` = ' . prepare($nome)) == 0) {
                    $dbo->query(
                        'INSERT INTO `sp_spedizioni` (`nome_percorso`, `note`, `punto_di_partenza`)
                        VALUES (' . prepare($nome_percorso) . ', ' . prepare($note) . ', ' .prepare($punto_di_partenza) . ')'
                    );

                    $id_record = $dbo->lastInsertedID();

                    for ($i=0; $i<count($id_consegna)-1; $i++) {
                        $dbo->query(
                            'INSERT INTO `sp_spedizioni_dettaglio`
                            (`id_spedizione`, `id_consegna`,`ragione_sociale`, `numero_documento`, `colli`, `indirizzo`, `citta`,
                            `cap`, `provincia`, `tipo_consegna`)
                            VALUES (' . prepare($id_record) . ', ' . prepare($id_consegna[$i]) . ', ' . prepare($ragione_sociale[$i]) .
                            ', ' . prepare($numero_documento[$i]) . ', ' . prepare($colli[$i]) . ', ' . prepare($indirizzo[$i]) .
                            ', ' . prepare($citta[$i]) . ', ' . prepare($cap[$i]) . ', ' . prepare($provincia[$i]) .
                            ', ' . prepare($tipo_consegna[$i]) . ')'
                        );
                    }

                    if (isAjaxRequest()) {
                        echo json_encode(['id' => $id_record, 'text' => $nome]);
                    }

                    flash()->info(tr('Aggiunta nuova relazione _NAME_', [
                        '_NAME_' => $nome,
                    ]));
                } else {
                    flash()->error(tr("E' già presente una relazione di _NAME_.", [
                        '_NAME_' => $nome,
                    ]));
                }
            } else {
                flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
            }

            break;


    /*case 'update':
        $nome = filter('nome_percorso');
        $note = filter('note');
        $data = filter('data_di_partenza');

        if (isset($nome)) {
            if ($dbo->fetchNum('SELECT * FROM `sp_spedizioni` WHERE `nome`='.prepare($nome).' AND `id`!='.prepare($id_record)) == 0) {
                $dbo->query(
                    'UPDATE `sp_spedizioni`
                    SET `nome_percorso`='.prepare($nome). ', `note`='.prepare($note). ', `data_di_partenza`='.prepare($data).
                    ' WHERE `id`='.prepare($id_record));
                flash()->info(tr('Salvataggio completato.'));
            } else {
                flash()->error(tr("E' già presente una relazione _NAME_.", [
                    '_TYPE_' => $descrizione,
                ]));
            }
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;*/

    /*case 'delete':
        $righe = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idrelazione='.prepare($id_record));

        if (isset($id_record) && empty($righe)) {
            $dbo->query('DELETE FROM `an_relazioni` WHERE `id`='.prepare($id_record));
            flash()->info(tr('Relazione _NAME_ eliminata con successo!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            flash()->error(tr('Sono presenti '.count($righe).' anagrafiche collegate a questa relazione.'));
        }

        break;*/
}
