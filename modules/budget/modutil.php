<?php

use Carbon\Carbon;

include_once __DIR__.'/../../core.php';

/*
    ANDAMENTO ECONOMICO
*/

function ricavi_reali($m, $anno)
{
    $dbo = database();

    $movimenti_fatture = $dbo->fetchArray('
        SELECT 
            co_movimenti.data,
            co_movimenti.descrizione,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica,
            -SUM(totale) AS totale
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id LEFT JOIN co_documenti ON 
            co_movimenti.iddocumento=co_documenti.id LEFT JOIN co_tipidocumento ON
            co_documenti.idtipodocumento=co_tipidocumento.id
        WHERE
            MONTH(co_movimenti.data) = '.prepare($m).'
            AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
            AND
            co_pianodeiconti1.descrizione = "Economico"
            AND
            iddocumento!=0 AND co_tipidocumento.dir="entrata"
        GROUP BY
            idmastrino
    ');

    $movimenti_manuali = $dbo->fetchArray('
        SELECT 
            co_movimenti.data,
            co_movimenti.descrizione,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica,
            -SUM(totale) AS totale
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id LEFT JOIN co_documenti ON 
            co_movimenti.iddocumento=co_documenti.id LEFT JOIN co_tipidocumento ON
            co_documenti.idtipodocumento=co_tipidocumento.id
        WHERE
            MONTH(co_movimenti.data) = '.prepare($m).'
            AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
            AND
            co_pianodeiconti1.descrizione = "Economico"
            AND
            iddocumento=0
        GROUP BY
            idmastrino
        HAVING
            totale > 0
    ');

    $result = array_merge((array)$movimenti_fatture, (array)$movimenti_manuali);
    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = abs($totale);

    return $result;
}

function ricavi_reali_gruppi($start, $end)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT 
            co_pianodeiconti3.descrizione AS conto,
            -SUM(totale) AS totale,
            MONTH(co_movimenti.data) AS mese, 
            YEAR(co_movimenti.data) AS anno
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id
        WHERE
            co_movimenti.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            AND
            co_pianodeiconti1.descrizione = "Economico"
        GROUP BY
            co_pianodeiconti3.id, 
            MONTH(co_movimenti.data), 
            YEAR(co_movimenti.data)
        HAVING
            totale > 0
        ORDER BY
            totale DESC
    ');

    $totale = 0;
    foreach ($result as $r) {
        $totale += abs($r['totale']);
    }
    $result[0]['importo'] = abs($totale);

    return $result;
    
}

function ricavi_previsionali($m, $anno)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            (totale) AS totale,
            bu_previsionale.descrizione,
            bu_previsionale.data,
            (SELECT co_pianodeiconti3.descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=bu_previsionale.id_conto) AS conto, 
            (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica) AS anagrafica
        FROM
            bu_previsionale INNER JOIN co_pianodeiconti3 ON bu_previsionale.id_conto=co_pianodeiconti3.id
        WHERE
            bu_previsionale.sezione IN("economico", "economico_finanziario")
            AND
            MONTH(bu_previsionale.data) = '.prepare($m).'
            AND
            YEAR(bu_previsionale.data) = '.prepare($anno).'
            AND
            bu_previsionale.data >= CURDATE()
            AND
            tipo="entrata"
    ');

    //Se ci sono delle sorgenti esterne le aggiungo alla creazione delle previsioni
    $rs = $dbo->fetchArray("SELECT query FROM bu_sorgenti WHERE enabled=1 AND sezionale='vendite' ");
    foreach ($rs as $sorgente) {
        if (!empty($sorgente['query'])) {
            //Replace valori
            $sorgente['query'] = str_replace("|mese|", str_pad($m, 2, '0', STR_PAD_LEFT), $sorgente['query']);
            $sorgente['query'] = str_replace("|anno|", $anno, $sorgente['query']);

            $rs_s = $dbo->fetchArray($sorgente['query']);
            $result = array_merge((array)$result, (array)$rs_s);
        }
    }

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function costi_reali($m, $anno)
{
    $dbo = database();

    $movimenti_fatture = $dbo->fetchArray('
        SELECT 
            co_movimenti.data,
            co_movimenti.descrizione,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica,
            -SUM(totale) AS totale
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id LEFT JOIN co_documenti ON 
            co_movimenti.iddocumento=co_documenti.id LEFT JOIN co_tipidocumento ON
            co_documenti.idtipodocumento=co_tipidocumento.id
        WHERE
            MONTH(co_movimenti.data) = '.prepare($m).'
            AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
            AND
            co_pianodeiconti1.descrizione = "Economico"
            AND
            iddocumento!=0 AND co_tipidocumento.dir="uscita"
        GROUP BY
            idmastrino
    ');

    $movimenti_manuali = $dbo->fetchArray('
        SELECT 
            co_movimenti.data,
            co_movimenti.descrizione,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica,
            -SUM(totale) AS totale
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id LEFT JOIN co_documenti ON 
            co_movimenti.iddocumento=co_documenti.id LEFT JOIN co_tipidocumento ON
            co_documenti.idtipodocumento=co_tipidocumento.id
        WHERE
            MONTH(co_movimenti.data) = '.prepare($m).'
        AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
        AND
            co_pianodeiconti1.descrizione = "Economico"
        AND
            iddocumento=0
        GROUP BY
            idmastrino
        HAVING
            totale < 0
    ');

    $result = array_merge((array)$movimenti_fatture, (array)$movimenti_manuali);
    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = -abs($totale);

    return $result;
}

function costi_reali_gruppi($start, $end)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT 
            co_pianodeiconti3.descrizione AS conto,
            -SUM(totale) AS totale,
            MONTH(co_movimenti.data) AS mese, 
            YEAR(co_movimenti.data) AS anno
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id
        WHERE
            co_movimenti.data BETWEEN '.prepare($start).' AND '.prepare($end).'
            AND
            co_pianodeiconti1.descrizione = "Economico"
        GROUP BY
            co_pianodeiconti3.id, 
            MONTH(co_movimenti.data), 
            YEAR(co_movimenti.data)
        HAVING
            totale < 0
        ORDER BY totale ASC
    ');

    $totale = 0;
    foreach ($result as $r) {
        $totale += abs($r['totale']);
    }
    $result[0]['importo'] = abs($totale);

    return $result;
    
}

function costi_previsionali($m, $anno)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            (totale) AS totale,
            bu_previsionale.descrizione,
            bu_previsionale.data,
            (SELECT co_pianodeiconti3.descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=bu_previsionale.id_conto) AS conto, 
            (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica) AS anagrafica
        FROM
            bu_previsionale INNER JOIN co_pianodeiconti3 ON bu_previsionale.id_conto=co_pianodeiconti3.id
        WHERE
            bu_previsionale.sezione IN("economico", "economico_finanziario")
            AND
            MONTH(bu_previsionale.data) = '.prepare($m).'
            AND
            YEAR(bu_previsionale.data) = '.prepare($anno).'
            AND
            bu_previsionale.data >= CURDATE()
            AND
            tipo="uscita"
    ');

    //Se ci sono delle sorgenti esterne le aggiungo alla creazione delle previsioni
    $rs = $dbo->fetchArray("SELECT query FROM bu_sorgenti WHERE enabled=1 AND sezionale='acquisti' ");
    foreach ($rs as $sorgente) {
        if (!empty($sorgente['query'])) {
            //Replace valori
            $sorgente['query'] = str_replace("|mese|", str_pad($m, 2, '0', STR_PAD_LEFT), $sorgente['query']);
            $sorgente['query'] = str_replace("|anno|", $anno, $sorgente['query']);

            $rs_s = $dbo->fetchArray($sorgente['query']);
            $result = array_merge((array)$result, (array)$rs_s);
        }
    }

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function utile_reale($m, $anno)
{
    $ricavi = ricavi_reali($m, $anno);
    $costi = costi_reali($m, $anno);
    $totale = sum(array_column($ricavi, 'importo'), array_column($costi, 'importo'));
    $result[0]['importo'] = $totale;

    return $result;
}

function utile_previsionale($m, $anno)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            (totale) AS totale,
            bu_previsionale.descrizione,
            bu_previsionale.data
        FROM
            bu_previsionale INNER JOIN co_pianodeiconti3 ON bu_previsionale.id_conto=co_pianodeiconti3.id
        WHERE
            bu_previsionale.sezione IN("economico", "economico_finanziario")
            AND
            MONTH(bu_previsionale.data) = '.prepare($m).'
            AND
            YEAR(bu_previsionale.data) = '.prepare($anno).'
            AND
            bu_previsionale.data >= CURDATE()
    ');

    //Se ci sono delle sorgenti esterne le aggiungo alla creazione delle prevision
    $rs = $dbo->fetchArray("SELECT query FROM bu_sorgenti WHERE enabled=1 ");
    foreach ($rs as $sorgente) {
        if (!empty($sorgente['query'])) {
            //Replace valori
            $sorgente['query'] = str_replace("|mese|", str_pad($m, 2, '0', STR_PAD_LEFT), $sorgente['query']);
            $sorgente['query'] = str_replace("|anno|", $anno, $sorgente['query']);
            $sorgente['query'] = str_replace("|iva_predefinita|", $iva_predefinita, $sorgente['query']);

            $rs_s = $dbo->fetchArray($sorgente['query']);
            $result = array_merge((array)$result, (array)$rs_s);
        }
    }

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

/*
    ANDAMENTO FINANZIARIO
*/

function entrate_reali($m, $anno)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            co_movimenti.*, 
            (totale) AS totale,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2                        
        WHERE
            co_pianodeiconti2.descrizione="Cassa e banche"
            AND
            MONTH(co_movimenti.data) = '.prepare($m).'
            AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
            AND
            primanota=1
            AND
            totale>0
    ');

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
        $r['totale'] = -$r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function entrate_previsionali($m, $anno)
{
    global $iva_predefinita;
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            (totale*IFNULL(pagamenti2.prc/100, 1)+totale*IFNULL(pagamenti2.prc/100, 1)/100*IFNULL(co_iva.percentuale, '.prepare($iva_predefinita).' )) AS totale,
            (SELECT co_pianodeiconti3.descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=bu_previsionale.id_conto) AS conto, 
            (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica) AS anagrafica,
            bu_previsionale.descrizione,
            CONCAT( YEAR(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY), "-", MONTH(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY), "-01") AS `data`
        FROM
            bu_previsionale INNER JOIN co_pianodeiconti3 ON bu_previsionale.id_conto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica LEFT JOIN co_iva ON co_iva.id=an_anagrafiche.idiva_vendite
            LEFT JOIN co_pagamenti ON an_anagrafiche.idpagamento_vendite=co_pagamenti.id
            LEFT JOIN co_pagamenti AS pagamenti2 ON co_pagamenti.descrizione=pagamenti2.descrizione
        WHERE
            bu_previsionale.sezione IN("finanziario", "economico_finanziario")
            AND
            bu_previsionale.tipo="entrata" 
            AND 
            MONTH(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) = '.prepare($m).' 
            AND 
            YEAR(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) = '.prepare($anno).'
            AND
            (bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) >= CURDATE()');


    //Se ci sono delle sorgenti esterne le aggiungo alla creazione delle previsioni
    $rs = $dbo->fetchArray("SELECT query2 FROM bu_sorgenti WHERE enabled=1 AND sezionale='vendite' ");
    foreach ($rs as $sorgente) {
        if (!empty($sorgente['query2'])) {
            //Sostituzione per i filtri temporali
            $sorgente['query2'] = str_replace("|mese|", str_pad($m, 2, '0', STR_PAD_LEFT), $sorgente['query2']);
            $sorgente['query2'] = str_replace("|anno|", $anno, $sorgente['query2']);
            $sorgente['query2'] = str_replace("|iva_predefinita|", $iva_predefinita, $sorgente['query2']);

            $rs_s = $dbo->fetchArray($sorgente['query2']);
            $result = array_merge((array)$result, (array)$rs_s);
        }
    }

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function uscite_reali($m, $anno)
{
    $dbo = database();

    $result = $dbo->fetchArray('
        SELECT
            co_movimenti.*, 
            co_movimenti.totale AS totale,
            CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " ", co_pianodeiconti3.descrizione) AS conto, 
            IFNULL((SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=co_movimenti.id_anagrafica), (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN co_documenti ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica WHERE co_documenti.id=co_movimenti.iddocumento)) AS anagrafica
        FROM
            co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2
        WHERE
            co_pianodeiconti2.descrizione="Cassa e banche"
            AND
            MONTH(co_movimenti.data) = '.prepare($m).'
            AND
            YEAR(co_movimenti.data) = '.prepare($anno).'
            AND
            primanota=1
            AND
            totale<0
    ');

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
        $r['totale'] = -$r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function uscite_previsionali($m, $anno)
{
    $dbo = database();
    $iva_predefinita = $dbo->fetchOne("SELECT percentuale FROM co_iva WHERE id=".prepare(setting('Iva predefinita')))['percentuale'];

    $result = $dbo->fetchArray('
        SELECT
            (totale*IFNULL(pagamenti2.prc/100, 1)+totale*IFNULL(pagamenti2.prc/100, 1)/100*IFNULL(co_iva.percentuale, '.prepare($iva_predefinita).' )) AS totale,
            (SELECT co_pianodeiconti3.descrizione FROM co_pianodeiconti3 WHERE co_pianodeiconti3.id=bu_previsionale.id_conto) AS conto, 
            (SELECT an_anagrafiche.ragione_sociale FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica) AS anagrafica,
            bu_previsionale.descrizione,
            CONCAT( YEAR(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY), "-", MONTH(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY), "-01") AS `data`
        FROM
            bu_previsionale INNER JOIN co_pianodeiconti3 ON bu_previsionale.id_conto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=bu_previsionale.id_anagrafica LEFT JOIN co_iva ON co_iva.id=an_anagrafiche.idiva_acquisti
            LEFT JOIN co_pagamenti ON an_anagrafiche.idpagamento_acquisti=co_pagamenti.id
            LEFT JOIN co_pagamenti AS pagamenti2 ON co_pagamenti.descrizione=pagamenti2.descrizione
        WHERE
            bu_previsionale.sezione IN("finanziario", "economico_finanziario")
            AND
            bu_previsionale.tipo="uscita" 
            AND 
            MONTH(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) = '.prepare($m).' 
            AND 
            YEAR(bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) = '.prepare($anno).'
            AND
            (bu_previsionale.data + INTERVAL IFNULL(pagamenti2.num_giorni, 0) DAY) >= CURDATE()');

    //Se ci sono delle sorgenti esterne le aggiungo alla creazione delle previsioni
    $rs = $dbo->fetchArray("SELECT nome, query2 FROM bu_sorgenti WHERE enabled=1 AND sezionale='acquisti' ");
    foreach ($rs as $sorgente) {
        if (!empty($sorgente['query2'])) {
            //Sostituzione per i filtri temporali
            $sorgente['query2'] = str_replace("|mese|", str_pad($m, 2, '0', STR_PAD_LEFT), $sorgente['query2']);
            $sorgente['query2'] = str_replace("|anno|", $anno, $sorgente['query2']);
            $sorgente['query2'] = str_replace("|iva_predefinita|", $iva_predefinita, $sorgente['query2']);

            $rs_s = $dbo->fetchArray($sorgente['query2']);
            $result = array_merge((array)$rs_s, (array)$result);
        }
    }

    $totale = 0;
    foreach ($result as $r) {
        $totale += $r['totale'];
    }
    $result[0]['importo'] = $totale;

    return $result;
}

function saldo_reale($m, $anno)
{
    $entrate = entrate_reali($m, $anno);
    $uscite = uscite_reali($m, $anno);
    $totale = sum(array_column($entrate, 'importo'), array_column($uscite, 'importo'));
    $result[0]['importo'] = $totale;

    return $result;
}

function saldo_previsionale($m, $anno)
{
    global $iva_predefinita;
    $dbo = database();

    $result[0]['importo'] = entrate_previsionali($m, $anno)[0]['importo'] + uscite_previsionali($m, $anno)[0]['importo'];

    return $result;
}


//Funzione per la visualizzazione dei mesi del filtro impostato
function get_months($start, $end)
{
    $mesi = [ '', tr('Gen'), tr('Feb'), tr('Mar'), tr('Apr'), tr('Mag'), tr('Giu'), tr('Lug'), tr('Ago'), tr('Set'), tr('Ott'), tr('Nov'), tr('Dic') ];
    $results = [];
    $mese = new Carbon($start);
    $n_mesi = $mese->diffInMonths($end);

    for ($i=1; $i<=$n_mesi+1; $i++) {
        $results[] = $mesi[ $mese->format('n') ];

        $mese->addMonth();
    }

    return json_encode($results, 1);
}

//FUNZIONI DI CALCOLO TOTALI PER CREAZIONE GRAFICI
function ricavi_reali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(ricavi_reali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function ricavi_totali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(ricavi_reali($mese->format('n'), $anno)[0]['importo'] + ricavi_previsionali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function costi_reali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$m] = number_format(costi_reali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function costi_totali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(costi_reali($mese->format('n'), $anno)[0]['importo'] + costi_previsionali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function utile_reale_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$m] = number_format(utile_reale($m, $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$m];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function utile_totale_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(utile_reale($mese->format('n'), $anno)[0]['importo'] + utile_previsionale($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function entrate_reali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(entrate_reali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function entrate_totali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(entrate_reali($mese->format('n'), $anno)[0]['importo'] + entrate_previsionali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function uscite_reali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(uscite_reali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function uscite_totali_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(uscite_reali($mese->format('n'), $anno)[0]['importo'] + uscite_previsionali($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function saldo_reale_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale[$anno.'-'.$mese->format('n')] = number_format(saldo_reale($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}

function saldo_totale_chart($start, $end)
{
    $mese = $start->copy();
    $anno = $mese->format('Y');

    $results = [];
    $totale_utile = [];

    for ($m=1; $m<=$start->diffInMonths($end)+1; $m++) {
        $totale_utile[$anno.'-'.$mese->format('n')] = number_format(saldo_reale($mese->format('n'), $anno)[0]['importo'] + saldo_previsionale($mese->format('n'), $anno)[0]['importo'], 2, ".", "");

        $results[] = $totale_utile[$anno.'-'.$mese->format('n')];

        $mese->addMonth();
        $anno = $mese->format('Y');
    }

    return $results;
}
