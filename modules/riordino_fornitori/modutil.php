<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

/**
 * Selezione sommaria di tutti gli articoli da ordinare con i campi utili da mostrare
 * in tabella. Per ogni articolo da ordinare è stata selezionata:
 * - quantità non consegnata al cliente
 * - quantità ordinata al fornitore
 * - quantità mancante
 *
 * @return array Articoli da ordinare
 */
function getArticoliDaOrdinare()
{
    $dbo = database();

    $articoli_da_ordinare = $dbo->fetchArray(
        "SELECT
            or_ordini.id_sede_partenza,
            or_ordini.id AS id,
            or_righe_ordini.idarticolo,
            mg_articoli.descrizione,
            or_ordini.numero,
            or_ordini.numero_esterno,

            or_ordini.data,
            SUM(IF((SELECT dir FROM or_tipiordine WHERE or_tipiordine.id = or_ordini.idtipoordine) = 'entrata', or_righe_ordini.qta - or_righe_ordini.qta_evasa, 0)) AS qta_non_consegnata_cliente,
            SUM(IF((SELECT dir FROM or_tipiordine WHERE or_tipiordine.id = or_ordini.idtipoordine) = 'uscita', or_righe_ordini.qta - or_righe_ordini.qta_evasa, 0)) AS qta_ordinata_fornitore,
            IF(mg_articoli_sedi.threshold_qta IS NULL, 0, mg_articoli_sedi.threshold_qta) AS minimo_sede,
            (SELECT SUM(qta) FROM mg_movimenti WHERE idarticolo = or_righe_ordini.idarticolo AND idsede = or_ordini.id_sede_partenza GROUP BY idsede) AS disponibilita_sede,
            mg_articoli.qta AS disponibilita_totale,
            SUM(IF((SELECT dir FROM or_tipiordine WHERE or_tipiordine.id = or_ordini.idtipoordine) = 'entrata', or_righe_ordini.qta - or_righe_ordini.qta_evasa, 0)) +
            IF(mg_articoli_sedi.threshold_qta IS NULL, 0, mg_articoli_sedi.threshold_qta) -
            SUM(IF((SELECT dir FROM or_tipiordine WHERE or_tipiordine.id = or_ordini.idtipoordine) = 'uscita', or_righe_ordini.qta - or_righe_ordini.qta_evasa, 0)) -
            (SELECT SUM(qta) FROM mg_movimenti WHERE idarticolo = or_righe_ordini.idarticolo AND idsede = or_ordini.id_sede_partenza GROUP BY idsede) AS qta_mancante,

            or_righe_ordini.um,
            IF(
                or_ordini.id_sede_partenza = 0,
                CONCAT_WS(' - ', 'Sede legale', CONCAT(citta, ' (', indirizzo, ')')),
                (
                    SELECT CONCAT_WS(' - ', nomesede, CONCAT(citta, ' (', indirizzo, ')'))
                    FROM an_sedi WHERE idanagrafica='1' AND or_ordini.id_sede_partenza = an_sedi.id
                )
            ) AS Magazzino
            FROM or_ordini
            INNER JOIN or_righe_ordini ON or_ordini.id = or_righe_ordini.idordine
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = 1
            INNER JOIN or_statiordine ON or_ordini.idstatoordine=or_statiordine.id
            INNER JOIN mg_articoli ON or_righe_ordini.idarticolo = mg_articoli.id
            LEFT JOIN mg_articoli_sedi ON mg_articoli.id = mg_articoli_sedi.id_articolo AND mg_articoli_sedi.id_sede = or_ordini.id_sede_partenza

            WHERE or_righe_ordini.idarticolo IN (SELECT or_righe_ordini.idarticolo
                FROM or_righe_ordini
                INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine
                INNER JOIN mg_articoli ON mg_articoli.id = or_righe_ordini.idarticolo
                INNER JOIN an_anagrafiche ON or_ordini.idanagrafica = an_anagrafiche.idanagrafica
                INNER JOIN an_sedi ON or_ordini.id_sede_partenza = an_sedi.id
                INNER JOIN or_statiordine ON or_ordini.idstatoordine=or_statiordine.id
                WHERE ((or_righe_ordini.qta - or_righe_ordini.qta_evasa) > 0)
                AND or_ordini.idtipoordine = 2 AND or_statiordine.impegnato = 1
                GROUP BY or_righe_ordini.idarticolo
            )
            AND (or_righe_ordini.qta - or_righe_ordini.qta_evasa) > 0
            AND or_righe_ordini.confermato = 1
            AND or_statiordine.impegnato = 1
            AND or_righe_ordini.idarticolo!=0
            AND mg_articoli.servizio = 0
            AND or_ordini.idstatoordine NOT IN (8,5)
            GROUP BY or_righe_ordini.idarticolo, Magazzino
            HAVING qta_mancante > 0"
    );

    return $articoli_da_ordinare;
}

/**
 * Restituisce i fornitori per gli articoli.
 *
 * @param array $articoli
 * @return array
 */
function getFornitoriArticoli($articoli)
{
    $dbo = database();

    foreach ($articoli as $articolo) {
        if (empty($ret[$articolo['idarticolo']])) {
            $results = $dbo->fetchArray(
                'SELECT idanagrafica as id, ragione_sociale as descrizione
                FROM an_anagrafiche ana
                WHERE ana.idanagrafica = 1
                UNION
                SELECT idanagrafica as id, ragione_sociale as descrizione
                FROM mg_fornitore_articolo fa
                INNER JOIN an_anagrafiche a ON fa.id_fornitore = a.idanagrafica
                WHERE id_articolo='.$articolo['idarticolo']
            );

            $ret[$articolo['idarticolo']] = $results;
        }
    }

    return $ret;
}

/**
 * Selezione dei fornitori in base a hasDistinte().
 *
 * @return array Elenco dei fornitori.
 */
function getFornitori()
{
    $dbo = database();

    if (!empty(hasDistinte())) {
        $order_by = 'ragione_sociale ASC';
        // Ordinamento impostato
        if ($tipo_fornitore == 'piu_economico') {
            $order_by = 'mg_fornitore_articolo.prezzo_acquisto ASC';
        } elseif ($tipo_fornitore == 'piu_alto') {
            $order_by = 'mg_fornitore_articolo.prezzo_acquisto DESC';
        } elseif ($tipo_fornitore == 'piu_rapido') {
            $order_by = 'mg_fornitore_articolo.giorni_consegna ASC';
        }

        $query_fornitori = "SELECT
            an_anagrafiche.idanagrafica AS id,
            CONCAT(
                ragione_sociale, '\r\nPrezzo: ', REPLACE(REPLACE(REPLACE(FORMAT(mg_prezzi_articoli.prezzo_unitario-mg_prezzi_articoli.prezzo_unitario/100*mg_prezzi_articoli.sconto_percentuale, 2), '.', '#'), ',', '.'), '#', ','), ' &euro; \r\nTempi consegna: ', giorni_consegna, 'gg'
            ) AS descrizione
            FROM an_anagrafiche
            INNER JOIN mg_fornitore_articolo ON an_anagrafiche.idanagrafica = mg_fornitore_articolo.id_fornitore
            LEFT JOIN mg_prezzi_articoli ON mg_prezzi_articoli.id_articolo = mg_fornitore_articolo.id_articolo
            WHERE mg_fornitore_articolo.id_articolo = ".prepare($articolo->id)." OR an_anagrafiche.idanagrafica = 1";

        $order = ' ORDER BY '.$order_by;

        $fornitore = $dbo->fetchOne($query_fornitori.' '.(!empty($id_fornitore) ? 'AND id_fornitore = '.prepare($id_fornitore) : '').$order);

        $query_fornitori .= $order;
        $fornitori = $dbo->fetchArray($query_fornitori);
    } else {
        $fornitori = $dbo->fetchArray(
            "SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione
            FROM an_anagrafiche
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica = an_tipianagrafiche_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
            WHERE an_tipianagrafiche.descrizione='Fornitore'"
        );

        if (sizeof($fornitori) == 1) {
            $fornitore = $fornitori[0];
        }
    }

    return $fornitori;
}

/**
 * Questa funzione controlla ogni articolo per determinare se è composto da sottoarticoli che sono da ordinare.
 *
 * @param Articolo $articolo codice dell'articolo da scollegare dall'ordine
 * @param $qta qta per ogni articolo da controllare
 */
function renderArticolo(Articolo $articolo = null, $qta_necessaria = null, $qta_disponibile = null, $id_sede = null)
{
    global $plugin_distinte;
    $dbo = database();

    $gestisciMagazzini = $dbo->fetchOne('SELECT * FROM zz_settings WHERE nome = "Gestisci soglia minima per magazzino"');

    if (!empty($id_sede)) {
        $threshold_qta = $dbo->fetchOne(
            'SELECT id_sede, threshold_qta FROM mg_articoli_sedi WHERE id_articolo = '.$articolo->id.' AND id_sede = '.$id_sede
        )['threshold_qta'];
    } else {
        $threshold_qta = $articolo->threshold_qta;
    }

    if ($gestisciMagazzini['valore'] == '1') {
        if ($id_sede == 0) {
            $nomesede = $articoloSedeLegale = $dbo->fetchOne(
                'SELECT "0" as id_sede , CONCAT("Sede legale - ", citta) as nomesede, mga.id as id_articolo, mga.threshold_qta
                FROM an_anagrafiche ana
                LEFT JOIN mg_articoli mga
                ON ana.idanagrafica = 1
                WHERE mga.id = ' . prepare($articolo->id) . ''
            )['nomesede'];
        } else {
            $nomesede = $dbo->fetchOne('SELECT nomesede FROM an_sedi WHERE id = '.$id_sede)['nomesede'];
        }
    }

    echo '
        <tr>
            <td>
                <input type="checkbox" class="check" id="checkbox_'.$articolo->id.'" name="ordinare['.$articolo->id.']">
            </td>

            <td>
                '.Modules::link('Articoli', $articolo->id, $articolo['codice'].' - '.$articolo['descrizione'], null).'
            </td>';

            if ($gestisciMagazzini['valore'] == '1') {
                echo '
                <td class="">
                    '.$nomesede.'
                </td>';
            }

            echo '
            <td class="text-right">
                '.Translator::numberToLocale($threshold_qta).'
            </td>

            <td class="text-right">
                '.Translator::numberToLocale($articolo->qta).'
            </td>

            <td class="text-right">
                '.Translator::numberToLocale($qta_disponibile - $articolo->qta).'
            </td>

            <td class="text-right">
                <input type="text" name="qta_ordinare['.$articolo->id.']" value="'.Translator::numberToLocale($qta_necessaria).'" class="form-control text-right">
            </td>
            <td>';


    $id_fornitore = get('idfornitore');
    $tipo_fornitore = get('fornitore');

    if (!empty($plugin_distinte)) {
        $order_by = 'ragione_sociale ASC';
        // Ordinamento impostato
        if ($tipo_fornitore == 'piu_economico') {
            $order_by = 'mg_fornitore_articolo.prezzo_acquisto ASC';
        } elseif ($tipo_fornitore == 'piu_alto') {
            $order_by = 'mg_fornitore_articolo.prezzo_acquisto DESC';
        } elseif ($tipo_fornitore == 'piu_rapido') {
            $order_by = 'mg_fornitore_articolo.giorni_consegna ASC';
        }

        $query_fornitori = "SELECT an_anagrafiche.idanagrafica AS id,
                CONCAT(ragione_sociale, '\r\nPrezzo: ', REPLACE(REPLACE(REPLACE(FORMAT(mg_prezzi_articoli.prezzo_unitario-mg_prezzi_articoli.prezzo_unitario/100*mg_prezzi_articoli.sconto_percentuale, 2), '.', '#'), ',', '.'), '#', ','), ' &euro; \r\nTempi consegna: ', giorni_consegna, 'gg') AS descrizione
            FROM an_anagrafiche
                INNER JOIN mg_fornitore_articolo ON an_anagrafiche.idanagrafica = mg_fornitore_articolo.id_fornitore
                LEFT JOIN mg_prezzi_articoli ON mg_prezzi_articoli.id_articolo = mg_fornitore_articolo.id_articolo
            WHERE mg_fornitore_articolo.id_articolo = ".prepare($articolo->id);
        $order = ' ORDER BY '.$order_by;

        $fornitore = $dbo->fetchOne($query_fornitori.' '.(!empty($id_fornitore) ? 'AND id_fornitore = '.prepare($id_fornitore) : '').$order);

        $query_fornitori .= $order;
        $fornitori = $dbo->fetchArray($query_fornitori);

        echo '
        {[ "type": "select", "class": "", "label": "", "name": "idanagrafica['.$articolo->id.']", "values": '.json_encode($fornitori).', "value": "'.$fornitore['id'].'" ]}';

    } else {
        $fornitori = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale AS descrizione
        FROM an_anagrafiche
            INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica = an_tipianagrafiche_anagrafiche.idanagrafica
            INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica
        WHERE an_tipianagrafiche.descrizione='Fornitore'");


        if (sizeof($fornitori) == 1) {
            $fornitore = $fornitori[0];
        }

        echo "<select class='form-control' name='idanagrafica[".$articolo->id."]'>";
        echo "<option value='0'></option>";
        foreach($fornitori AS $fornitore){
            echo "<option value='".$fornitore['id']."'>".$fornitore['descrizione']."</option>";
        }
        echo "</select>";
    }


    echo '
            </td>
        </tr>';
}

function hasDistinte()
{
    return database()->fetchNum("SELECT * FROM zz_plugins WHERE name='Distinta base'");
}

function scomponi_articoli($list)
{
    $has_distinta = hasDistinte();

    $considered_list = [];
    $previous = fixQuantitaDisponibile($list, $considered_list);

    do {
        $ricorsione_completa = true;

        // Scomposizione di un livello
        $current = scomponiLivello($previous, $ricorsione_completa);

        // Compattazione della lista
        $compacted = compactList($current);

        // Correzione quantità sulla base degli ordini e del magazzino
        $components = fixQuantitaDisponibile($compacted, $considered_list);

        // Ricorsione di livello
        $previous = $components;
    } while (!$ricorsione_completa);

    return $components;
}

function scomponiLivello($list, &$ricorsione_completa)
{
    $has_distinta = hasDistinte();

    $results = [];

    // Scomposizione di un livello
    foreach ($list as $element) {
        $articolo = $element['articolo'];
        $qta = $element['qta_necessaria'];

        // Elenco basato sulle distinte base
        if (hasDistinte()) {
            $componenti = $articolo->componenti;
            if (!$componenti->isEmpty()) {
                foreach ($componenti as $componente) {
                    $qta_componente = $qta * $componente->pivot->qta;

                    // Individuazione della scomposizione
                    $results[] = [
                        'articolo' => $componente,
                        'qta_necessaria' => $qta_componente,
                    ];
                }
            }

            $ricorsione_completa &= $componenti->isEmpty();
        }

        if (!$has_distinta || $componenti->isEmpty()) {
            $results[] = $element;
        }
    }

    return $results;
}

/**
 * Compattazione della lista.
 *
 * @param $list
 *
 * @return array
 */
function compactList($list)
{
    $results = [];

    foreach ($list as $element) {
        $id_articolo = $element['articolo']->id;
        $qta = $element['qta_necessaria'];

        $qta_necessaria = $results[$id_articolo]['qta_necessaria'] ?: 0;
        $qta_necessaria += $qta;

        $results[$id_articolo] = [
            'articolo' => $element['articolo'],
            'qta_necessaria' => $qta_necessaria,
            'qta_disponibile' => $element['qta_disponibile'],
        ];
    }

    return $results;
}

/**
 * Correzione quantità sulla base degli ordini e del magazzino.
 *
 * @param $list
 *
 * @return array
 */
function fixQuantitaDisponibile($list, &$considered_list)
{
    $components = [];

    foreach ($list as $element) {
        $articolo = $element['articolo'];
        $qta = $element['qta_necessaria'];

        if (!isset($considered_list[$articolo->id])) {
            $qta_ordinata = database()->fetchOne("SELECT
            SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) as qta_ordinata
        FROM or_righe_ordini
            INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine
        WHERE
            idtipoordine = (SELECT id FROM or_tipiordine WHERE dir = 'uscita') AND
            or_righe_ordini.qta > or_righe_ordini.qta_evasa AND
            or_righe_ordini.idarticolo = ".prepare($articolo->id))['qta_ordinata'];

            $qta_disponibile = floatval($qta_ordinata) + $articolo->qta;
            $qta = $qta - $qta_disponibile;

            $considered_list[$articolo->id] = $qta < 0 ? abs($qta) : 0;
        } else {
            $qta = $qta - $considered_list[$articolo->id];
        }

        if ($qta > 0) {
            $components[$articolo->id] = [
                'articolo' => $articolo,
                'qta_necessaria' => $qta,
                'qta_disponibile' => $element['qta_disponibile'] ?: $qta_disponibile,
            ];
        }
    }

    return $components;
}
