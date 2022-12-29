<?php

require __DIR__.'/vendor/autoload.php';

/**
 * Funzione per inserire i movimenti di magazzino per le vendite al banco.
 */
function add_movimento_magazzino_venditabanco($idarticolo, $qta, $movimento = '', $idvendita = '0', $idmagazzino = null)
{
    global $dbo;

        // Operazioni di carico
        if ($qta >= 0) {
            // Vendita da banco
            if ($idvendita != '0') {
                $rs = $dbo->fetchArray("SELECT numero, DATE_FORMAT(data, '%d/%m/%Y ore %H:%i' ) as data FROM vb_venditabanco WHERE id='".$idvendita."'");
                $movimento = 'Carico magazzino - Vendita al banco num. '.$rs[0]['numero'].' del '.$rs[0]['data'];
            }
        }
    
        // Operazioni di scarico
        elseif ($qta < 0) {
            // Vendita da banco
            if ($idvendita != '0') {
                $rs = $dbo->fetchArray("SELECT numero, DATE_FORMAT(data, '%d/%m/%Y ore %H:%i' ) as data FROM vb_venditabanco WHERE id='".$idvendita."'");
                $movimento = 'Scarico magazzino - Vendita al banco num. '.$rs[0]['numero'].' del '.$rs[0]['data'];
            }
        }
        $reference_type = 'Modules\VenditaBanco\Vendita';
    
        $dbo->query('INSERT INTO mg_movimenti( idarticolo, qta, movimento, reference_id, reference_type, data, idsede ) VALUES( '.prepare($idarticolo).', '.prepare($qta).', '.prepare($movimento).',  '.prepare($idvendita).', '.prepare($reference_type).', "'.date('Y-m-d').'", '.prepare($idmagazzino).' )');
}

/**
 * Questa funzione rimuove un articolo dalla vendita e lo riporta in magazzino.
 */
function rimuovi_articolo_davenditabanco($idarticolo, $idvendita, $idmagazzino = null)
{
    global $dbo;
    global $dir;

    //recupero magazzino della vendita
    if (empty($idmagazzino)) {
        $idmagazzino = $dbo->fetchOne('SELECT idmagazzino FROM vb_venditabanco WHERE id ='.prepare('$idvendita'))['idmagazzino'];
    }

    // Leggo la quantità di questo articolo nella vendita
    $riga = $dbo->fetchOne('SELECT * FROM vb_venditabanco_righe WHERE idarticolo = '.prepare($idarticolo).' AND idvendita = '.prepare($idvendita));
    $qta = floatval($riga['qta']);

    // ripristino qta su magazzino principale o secondari
    if (empty($idmagazzino)) {
        $dbo->query('UPDATE mg_articoli SET qta=qta+'.$qta.', attivo=1 WHERE id='.prepare($idarticolo));
    } else {
        $dbo->query('UPDATE mg_articoli_automezzi SET qta=qta+'.$qta.' WHERE id='.prepare($idarticolo).' AND id = '.$idmagazzino."'");
    }

    // registro movimento
    add_movimento_magazzino_venditabanco($idarticolo, $qta, '', $idvendita, $idmagazzino);

    // Elimino la riga dalla vendita al banco
    $dbo->query('DELETE FROM `vb_venditabanco_righe` WHERE id='.prepare($riga['id']));

    return $riga['idarticolo'];
}

/**
 * Questa funzione aggiunge un articolo nella vendita.
 */
function add_articolo_invenditabanco($idvendita, $idarticolo, $idmagazzino = null)
{
    global $dbo;
    global $id_record;

    //recupero magazzino della vendita, nel caso non sia settato
    if (empty($idmagazzino)) {
        $idmagazzino = $dbo->fetchOne('SELECT idmagazzino FROM vb_venditabanco WHERE id ='.prepare('$idvendita'))['idmagazzino'];
    }

    // Lettura unità di misura dell'articolo
    $query = 'SELECT um FROM mg_articoli WHERE id='.prepare($idarticolo);
    $rs = $dbo->fetchArray($query);
    $um = $rs[0]['um'];

    // Lettura articolo
    $query = 'SELECT id, descrizione, idiva_vendita, prezzo_vendita, barcode FROM mg_articoli WHERE id='.prepare($idarticolo);

    $rs = $dbo->fetchArray($query);
    $barcode = $rs[0]['barcode'];

    $descrizione = stripslashes($rs[0]['descrizione']);
    $idiva = ($rs[0]['idiva_vendita'] ? $rs[0]['idiva_vendita'] : setting('Iva predefinita'));
    $qta = 1;
    $prezzo = $rs[0]['prezzo_vendita'];

    //Se ho già inserito un articolo prendo prezzo iva e sconto da quello
    $query2 = 'SELECT * FROM vb_venditabanco_righe WHERE idarticolo='.prepare($idarticolo).' AND idvendita='.prepare($idvendita);
    $rs2 = $dbo->fetchArray($query2);

    if (sizeof($rs2) > 0) {
        $prezzo = $rs2[0]['subtotale'];
        $sconto = $rs2[0]['sconto'];
        $idiva = $rs2[0]['idiva'];
    }

    // Decremento la quantità dell'articolo (,attivo=1)
    $dbo->query('UPDATE mg_articoli SET qta=qta-'.$qta." WHERE id='".$idarticolo."'");

    // Registro movimento
    add_movimento_magazzino_venditabanco($idarticolo, -$qta, '', $idvendita, $idmagazzino);

    // Lettura iva dell'articolo
    $rs2 = $dbo->fetchArray("SELECT percentuale, indetraibile FROM co_iva WHERE id='".$idiva."'");
    $iva = ($prezzo - $sconto) / 100 * $rs2[0]['percentuale'];
    $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

    // Inserisco la riga per la vendita al banco
    $result = $dbo->insert('vb_venditabanco_righe', [
        'idvendita' => ($idvendita),
        'idarticolo' => ($idarticolo),
        'barcode' => isset($barcode) ? $barcode : '',
        'idiva' => isset($idiva) ? $idiva : 0,
        'descrizione' => ($descrizione),
        'subtotale' => ($prezzo),
        'um' => ($um),
        'qta' => ($qta),
        'sconto' => isset($sconto) ? $sconto : 0,
    ]);
}

function aggiungi_movimento_venditabanco($idvendita, $primanota = 0)
{
    $dbo = Database::getConnection();

    $rs_vb = $dbo->fetchArray('SELECT * FROM vb_venditabanco WHERE id='.prepare($idvendita));
    $data_documento = $rs_vb[0]['data'];
    $data = $data_documento;

    $query = 'SELECT SUM(vb_venditabanco_righe.subtotale - vb_venditabanco_righe.sconto) AS imponibile FROM vb_venditabanco_righe GROUP BY idvendita HAVING idvendita='.prepare($idvendita);
    $rs = $dbo->fetchArray($query);

    // Imponibile vendita
    $imponibile_vendita = sum($rs[0]['imponibile'], null, 2);

    $query = 'SELECT SUM((subtotale-sconto)*(SELECT percentuale FROM co_iva WHERE co_iva.id=vb_venditabanco_righe.idiva)/100) AS iva FROM vb_venditabanco_righe GROUP BY idvendita HAVING idvendita='.prepare($idvendita);
    $rs = $dbo->fetchArray($query);

    // Lettura iva delle righe in fattura
    $iva_vendita = sum($rs[0]['iva'], null, 2);

    $totale_vendita = $imponibile_vendita + $iva_vendita;

    // Preparazione dei conti
    $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti'";
    $rs = $dbo->fetchArray($query);
    $idconto_controparte = $rs[0]['id'];

    // Aggiungo il movimento con il totale sul conto "Riepilogativo clienti"
    $idmastrino = get_new_idmastrino();
    $numero = $rs_vb[0]['numero'];
    $descrizione = 'Vendita al banco numero '.$numero;

    $query1 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', "0", "",  '.prepare($descrizione.' del '.date('d/m/Y', strtotime($data))).', '.prepare($idconto_controparte).', '.prepare(($totale_vendita) * 1).', '.prepare($primanota).' )';
    $dbo->query($query1);
    $idmovimento = $dbo->last_inserted_id();
    $dbo->query('INSERT INTO vb_venditabanco_movimenti(idmovimento, idvendita) VALUES('.prepare($idmovimento).', '.prepare($idvendita).')');

    $query2 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', "0", "",  '.prepare($descrizione.' del '.date('d/m/Y', strtotime($data))).', '.prepare($idconto_controparte).', '.prepare(($totale_vendita) * -1).', "1" )';
    $dbo->query($query2);
    $idmovimento = $dbo->last_inserted_id();
    $dbo->query('INSERT INTO vb_venditabanco_movimenti(idmovimento, idvendita) VALUES('.prepare($idmovimento).', '.prepare($idvendita).')');

    // Aggiungo il movimento con l'imponibile nel conto definito dal metodo di pagamento
    $rsconto_pagamento = $dbo->fetchArray('SELECT idconto_vendite FROM co_pagamenti INNER JOIN vb_venditabanco ON co_pagamenti.id=vb_venditabanco.idpagamento WHERE vb_venditabanco.id='.prepare($idvendita));
    $idconto_pagamento = $rsconto_pagamento[0]['idconto_vendite'];

    $query3 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', "0", "",  '.prepare($descrizione.' del '.date('d/m/Y', strtotime($data))).', '.prepare($idconto_pagamento).', '.prepare(($totale_vendita) * 1).', "1" )';
    $dbo->query($query3);
    $idmovimento = $dbo->last_inserted_id();
    $dbo->query('INSERT INTO vb_venditabanco_movimenti(idmovimento, idvendita) VALUES('.prepare($idmovimento).', '.prepare($idvendita).')');

    $query = "SELECT id FROM co_pianodeiconti3 WHERE descrizione='Ricavi vendita al banco'";
    $rs = $dbo->fetchArray($query);

    $query4 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', "0", "",  '.prepare($descrizione.' del '.date('d/m/Y', strtotime($data))).', '.prepare($rs[0]['id']).', '.prepare($imponibile_vendita * -1).', '.prepare($primanota).')';
    $dbo->query($query4);
    $idmovimento = $dbo->last_inserted_id();
    $dbo->query('INSERT INTO vb_venditabanco_movimenti(idmovimento, idvendita) VALUES('.prepare($idmovimento).', '.prepare($idvendita).')');

    // Aggiunco il movimento con il totale dell'iva

    if ($iva_vendita != 0) {
        $descrizione_conto_iva = 'Iva su vendite';
        $query = 'SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='.prepare($descrizione_conto_iva);
        $rs = $dbo->fetchArray($query);
        $idconto_iva = $rs[0]['id'];
        $descrizione_conto_iva = $rs[0]['descrizione'];

        $query5 = 'INSERT INTO co_movimenti(idmastrino, data, data_documento, iddocumento, idanagrafica,  descrizione, idconto, totale, primanota) VALUES('.prepare($idmastrino).', '.prepare($data).', '.prepare($data_documento).', "0", "",  '.prepare($descrizione.' del '.date('d/m/Y', strtotime($data))).', '.prepare($idconto_iva).', '.prepare($iva_vendita * -1).', '.prepare($primanota).')';
        $dbo->query($query5);
        $idmovimento = $dbo->last_inserted_id();
        $dbo->query('INSERT INTO vb_venditabanco_movimenti(idmovimento, idvendita) VALUES('.prepare($idmovimento).', '.prepare($idvendita).')');
    }
}

/**
 * Elimina i movimenti collegati ad una fattura.
 */
function elimina_movimento_venditabanco($idvendita, $anche_prima_nota = 0)
{
    $dbo = Database::getConnection();

    $query = 'DELETE FROM co_movimenti WHERE id IN(SELECT idmovimento FROM vb_venditabanco_movimenti WHERE idvendita='.prepare($idvendita).') AND primanota='.prepare($anche_prima_nota);
    $dbo->query($query);

    $query2 = 'DELETE FROM vb_venditabanco_movimenti WHERE idvendita='.prepare($idvendita).' AND idmovimento NOT IN(SELECT id FROM co_movimenti)';
    $dbo->query($query2);
}
