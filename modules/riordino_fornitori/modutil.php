<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

/**
 * Questa funzione controlla ogni articolo per determinare se è composto da sottoarticoli che sono da ordinare.
 *
 * @param Articolo $articolo codice dell'articolo da scollegare dall'ordine
 * @param $qta qta per ogni articolo da controllare
 */
function renderArticolo(Articolo $articolo, $qta_necessaria = null, $qta_disponibile = null)
{
    global $plugin_distinte;
    $dbo = database();

    echo '
        <tr>
            <td>
                <input type="checkbox" class="check" id="checkbox_'.$articolo->id.'" name="ordinare['.$articolo->id.']">
            </td>
            
            <td>
                '.Modules::link('Articoli', $articolo->id, $articolo['codice'].' - '.$articolo['descrizione'], null).'
            </td>

            <td class="text-right">
                '.Translator::numberToLocale($articolo->threshold_qta).'
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
