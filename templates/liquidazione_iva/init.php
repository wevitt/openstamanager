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

$date_start = filter('date_start');
$date_end = filter('date_end');

$anno_precedente_start = (new Carbon($date_start))->subYears(1)->format('Y-m-d');
$anno_precedente_end = (new Carbon($date_end))->subYears(1)->format('Y-m-d');

$periodo = $dbo->fetchOne('SELECT valore FROM zz_settings WHERE nome="Liquidazione iva"');
if ($periodo['valore'] == 'Mensile') {
    $periodo_precedente_start = (new Carbon($date_start))->startOfYear()->format('Y-m-d');
    $periodo_precedente_end = (new Carbon($date_end))->startOfMonth()->subMonth()->endOfMonth()->format('Y-m-d');
} else {
    $periodo_precedente_start = (new Carbon($date_start))->startOfYear()->format('Y-m-d');
    $periodo_precedente_end = (new Carbon($date_end))->startOfMonth()->subMonths(3)->endOfMonth()->format('Y-m-d');
}

$id_proforma_vendita = $dbo->fetchOne("SELECT id FROM zz_segments WHERE id_module = 14 AND name='Fatture pro-forma'")['id'];
$id_proforma_acquisti = $dbo->fetchOne("SELECT id FROM zz_segments WHERE id_module = 15 AND name='Fatture pro-forma'")['id'];
$vendita_banco = $dbo->fetchNum("SELECT * FROM zz_modules WHERE name='Vendita al banco'");
$maggiorazione = 0;

// calcolo IVA su fatture + vendite al banco
if (!empty($vendita_banco)){
//debug('<hr><h4>iva_vendite_esigibile</h4>
$iva_vendite_esigibile = $dbo->fetchArray('
    SELECT
        cod_iva,
        aliquota,
        descrizione,
        SUM(iva) AS iva,
        SUM(subtotale) AS subtotale
    FROM
        (
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            IF (
                co_righe_documenti.idrivalsainps = 1,
                (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
                (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
            ) AS iva,
            SUM(subtotale - sconto) * IF (co_tipidocumento.reversed = 0, 1, -1) AS subtotale
        FROM
            co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
        WHERE
            co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND co_documenti.split_payment = 0 AND idstatodocumento NOT IN(
            SELECT
                id
            FROM
                co_statidocumento
            WHERE
                descrizione = "Bozza" OR descrizione = "Annullata"
        ) AND co_documenti.data_competenza >= '.prepare($date_start . ' 00:00:00').' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
        GROUP BY cod_iva, aliquota, descrizione
    UNION
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            SUM(vb_righe_venditabanco.iva) AS iva,
            SUM(
                vb_righe_venditabanco.subtotale - vb_righe_venditabanco.sconto
            ) AS subtotale
        FROM
            co_iva
        INNER JOIN vb_righe_venditabanco ON vb_righe_venditabanco.idiva = co_iva.id
        INNER JOIN vb_venditabanco ON vb_venditabanco.id = vb_righe_venditabanco.idvendita
        INNER JOIN vb_stati_vendita ON vb_venditabanco.idstato = vb_stati_vendita.id
        WHERE
            vb_venditabanco.data >= '.prepare($date_start . ' 00:00:00').' AND vb_venditabanco.data <= '.prepare($date_end . ' 23:59:59').' AND vb_righe_venditabanco.is_descrizione = 0 AND vb_stati_vendita.descrizione = "Pagato"
        GROUP BY
            cod_iva, aliquota, descrizione
        ) AS tabella
    GROUP BY
        cod_iva,
        aliquota,
        descrizione;');


//debug('<hr><h4>iva_vendite</h4>
$iva_vendite = $dbo->fetchArray('
    SELECT
        cod_iva,
        aliquota,
        descrizione,
        SUM(iva) AS iva,
        SUM(subtotale) AS subtotale
    FROM
        (
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            IF (
                co_righe_documenti.idrivalsainps = 1,
                (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
                (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
            ) AS iva,
            SUM(subtotale - sconto) * IF (co_tipidocumento.reversed = 0, 1, -1) AS subtotale
        FROM
            co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
        WHERE
            co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(
            SELECT
                id
            FROM
                co_statidocumento
            WHERE
                descrizione = "Bozza" OR descrizione = "Annullata"
        ) AND co_documenti.data_competenza >= '.prepare($date_start . ' 00:00:00').' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
        GROUP BY cod_iva, aliquota, descrizione
    UNION
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            SUM(vb_righe_venditabanco.iva) AS iva,
            SUM(
                vb_righe_venditabanco.subtotale - vb_righe_venditabanco.sconto
            ) AS subtotale
        FROM
            co_iva
        INNER JOIN vb_righe_venditabanco ON vb_righe_venditabanco.idiva = co_iva.id
        INNER JOIN vb_venditabanco ON vb_venditabanco.id = vb_righe_venditabanco.idvendita
        INNER JOIN vb_stati_vendita ON vb_venditabanco.idstato = vb_stati_vendita.id
        WHERE
            vb_venditabanco.data >= '.prepare($date_start  . ' 00:00:00').' AND vb_venditabanco.data <= '.prepare($date_end  . ' 23:59:59').' AND vb_righe_venditabanco.is_descrizione = 0 AND vb_stati_vendita.descrizione = "Pagato"
        GROUP BY
            cod_iva, aliquota, descrizione
        ) AS tabella
    GROUP BY
        cod_iva,
        aliquota,
        descrizione;');

//debug('<hr><h4>iva_vendite_anno_precedente</h4>
$iva_vendite_anno_precedente = $dbo->fetchArray('
    SELECT
        cod_iva,
        aliquota,
        descrizione,
        SUM(iva) AS iva,
        SUM(subtotale) AS subtotale
    FROM
        (
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            IF (
                co_righe_documenti.idrivalsainps = 1,
                (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
                (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
            ) AS iva,
            SUM(subtotale - sconto) * IF (co_tipidocumento.reversed = 0, 1, -1) AS subtotale
        FROM
            co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
        WHERE
            co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(
            SELECT
                id
            FROM
                co_statidocumento
            WHERE
                descrizione = "Bozza" OR descrizione = "Annullata"
        ) AND co_documenti.data_competenza >= '.prepare($anno_precedente_start . ' 00:00:00').' AND co_documenti.data_competenza <= '.prepare($anno_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
        GROUP BY cod_iva, aliquota, descrizione
    UNION
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            SUM(vb_righe_venditabanco.iva) AS iva,
            SUM(
                vb_righe_venditabanco.subtotale - vb_righe_venditabanco.sconto
            ) AS subtotale
        FROM
            co_iva
        INNER JOIN vb_righe_venditabanco ON vb_righe_venditabanco.idiva = co_iva.id
        INNER JOIN vb_venditabanco ON vb_venditabanco.id = vb_righe_venditabanco.idvendita
        INNER JOIN vb_stati_vendita ON vb_venditabanco.idstato = vb_stati_vendita.id
        WHERE
            vb_venditabanco.data >= '.prepare($anno_precedente_start  . ' 00:00:00').' AND vb_venditabanco.data <= '.prepare($anno_precedente_end  . ' 23:59:59').' AND vb_righe_venditabanco.is_descrizione = 0 AND vb_stati_vendita.descrizione = "Pagato"
        GROUP BY
            cod_iva, aliquota, descrizione
        ) AS tabella
    GROUP BY
        cod_iva,
        aliquota,
        descrizione;');

//debug('<hr><h4>iva_vendite_periodo_precedente</h4>
$iva_vendite_periodo_precedente = $dbo->fetchArray('
    SELECT
        cod_iva,
        aliquota,
        descrizione,
        SUM(iva) AS iva,
        SUM(subtotale) AS subtotale
    FROM
        (
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            IF (
                co_righe_documenti.idrivalsainps = 1,
                (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
                (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
            ) AS iva,
            SUM(subtotale - sconto) * IF (co_tipidocumento.reversed = 0, 1, -1) AS subtotale
        FROM
            co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
        WHERE
            co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(
            SELECT
                id
            FROM
                co_statidocumento
            WHERE
                descrizione = "Bozza" OR descrizione = "Annullata"
        ) AND co_documenti.data_competenza >= '.prepare($periodo_precedente_start . ' 00:00:00').' AND co_documenti.data_competenza <= '.prepare($periodo_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
        AND co_documenti.split_payment = 0
        GROUP BY cod_iva, aliquota, descrizione
    UNION
        SELECT
            co_iva.codice_natura_fe AS cod_iva,
            co_iva.percentuale AS aliquota,
            co_iva.descrizione AS descrizione,
            SUM(vb_righe_venditabanco.iva) AS iva,
            SUM(
                vb_righe_venditabanco.subtotale - vb_righe_venditabanco.sconto
            ) AS subtotale
        FROM
            co_iva
        INNER JOIN vb_righe_venditabanco ON vb_righe_venditabanco.idiva = co_iva.id
        INNER JOIN vb_venditabanco ON vb_venditabanco.id = vb_righe_venditabanco.idvendita
        INNER JOIN vb_stati_vendita ON vb_venditabanco.idstato = vb_stati_vendita.id
        WHERE
            vb_venditabanco.data >= '.prepare($periodo_precedente_start  . ' 00:00:00').' AND vb_venditabanco.data <= '.prepare($periodo_precedente_end  . ' 23:59:59').' AND vb_righe_venditabanco.is_descrizione = 0 AND vb_stati_vendita.descrizione = "Pagato"
        GROUP BY
            cod_iva, aliquota, descrizione
        ) AS tabella
    GROUP BY
        cod_iva,
        aliquota,
        descrizione;');

}

// calcolo IVA solo su fatture
else {

//debug('<hr><h4>iva_vendite_esigibile</h4>
$iva_vendite_esigibile = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND co_documenti.split_payment = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_vendite</h4>
$iva_vendite = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_vendite_anno_precedente</h4>
$iva_vendite_anno_precedente = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        SUM((co_righe_documenti.iva + iva_rivalsainps) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($anno_precedente_start).' AND co_documenti.data_competenza <= '.prepare($anno_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_vendite_periodo_precedente</h4>
$iva_vendite_periodo_precedente = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($periodo_precedente_start).' AND co_documenti.data_competenza <= '.prepare($periodo_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');
}

//debug('<hr><h4>iva_vendite_nonesigibile</h4>
$iva_vendite_nonesigibile = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "entrata" AND co_righe_documenti.is_descrizione = 0 AND co_documenti.split_payment = 1 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_acquisti_detraibile</h4>
$iva_acquisti_detraibile = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva * (100-indetraibile)/100) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva * (100-indetraibile)/100)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "uscita" AND co_righe_documenti.is_descrizione = 0 AND co_documenti.split_payment = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').' AND co_iva.indetraibile != 100
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_acquisti_nondetraibile</h4>
$iva_acquisti_nondetraibile = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva * (indetraibile)/100) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva * (indetraibile)/100)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "uscita" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= ' . prepare($date_start) . ' AND co_documenti.data_competenza <= ' . prepare($date_end . ' 23:59:59') . ' AND co_iva.indetraibile != 0
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//debug('<hr><h4>iva_acquisti</h4>
$iva_acquisti = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "uscita" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

    //debug('<hr><h4>iva_acquisti_anno_precedente</h4>
    $iva_acquisti_anno_precedente = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "uscita" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($anno_precedente_start).' AND co_documenti.data_competenza <= '.prepare($anno_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

    //debug('<hr><h4>iva_acquisti_periodo_precedente</h4>
    $iva_acquisti_periodo_precedente = $dbo->fetchArray('
    SELECT
        co_iva.codice_natura_fe AS cod_iva,
        co_iva.percentuale AS aliquota,
        co_iva.descrizione AS descrizione,
        IF (
            co_righe_documenti.idrivalsainps = 1,
            (SUM(iva * (1 - co_iva.indetraibile / 100)) + iva_rivalsainps) * (IF (co_tipidocumento.reversed = 0, 1,-1 )),
            (SUM(iva)) * (IF (co_tipidocumento.reversed = 0, 1,-1 ))
        ) AS iva,
        SUM((co_righe_documenti.subtotale - co_righe_documenti.sconto) *(IF(co_tipidocumento.reversed = 0,1,-1))) AS subtotale
    FROM
        co_iva
        INNER JOIN co_righe_documenti ON co_righe_documenti.idiva = co_iva.id
        INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
        INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
    WHERE
        co_tipidocumento.dir = "uscita" AND co_righe_documenti.is_descrizione = 0 AND idstatodocumento NOT IN(SELECT id FROM co_statidocumento WHERE descrizione = "Bozza" OR descrizione = "Annullata") AND co_documenti.data_competenza >= '.prepare($periodo_precedente_start . ' 00:00:00').' AND co_documenti.data_competenza <= '.prepare($periodo_precedente_end . ' 23:59:59').'
        AND co_documenti.id_segment != '.prepare($id_proforma_vendita).'
        AND co_documenti.id_segment != '.prepare($id_proforma_acquisti).'
    GROUP BY
        co_iva.id;');

//die;
