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

$dir = $_GET['dir'];

$id_sezionale = filter('id_sezionale');
$sezionale = $dbo->fetchOne('SELECT name FROM zz_segments WHERE id = '.$id_sezionale)['name'];

$date_start = filter('date_start');
$date_end = filter('date_end');

$tipo = $dir == 'entrata' ? 'vendite' : 'acquisti';
$vendita_banco = $dbo->fetchNum("SELECT * FROM zz_modules WHERE name='Vendita al banco'");

$v_iva = [];
$v_totale = [];

if ((!empty($vendita_banco)) AND (empty($id_sezionale)) AND ($tipo == 'vendite')){
    $query = '
        SELECT
            co_documenti.id,
            co_documenti.data_registrazione,
            co_documenti.data,
            IF(numero = "", numero_esterno, numero) AS numero,
            co_tipidocumento.codice_tipo_documento_fe,
            co_iva.percentuale,
            idiva,
            desc_iva AS descrizione,
            SUM(((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS iva,
            SUM(((subtotale-sconto)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS subtotale,
            SUM(((subtotale-sconto+iva+co_righe_documenti.rivalsainps-co_righe_documenti.ritenutaacconto)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS totale,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.codice AS codice_anagrafica
        FROM co_righe_documenti
            INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
            INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_documenti.idanagrafica
            INNER JOIN co_iva ON co_righe_documenti.idiva = co_iva.id
        WHERE idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata")
            and dir = '.prepare($dir).'
            AND is_descrizione = 0
            AND co_documenti.data_competenza >= '.prepare($date_start).'
            AND co_documenti.data_competenza <= '.prepare($date_end).'
            GROUP BY idiva, co_documenti.id
        UNION
        SELECT
            vb_venditabanco.id,
            vb_venditabanco.data as data_registrazione,
            vb_venditabanco.data_emissione as data,
            vb_venditabanco.numero_esterno as numero,
            "Vendita al banco" as codice_tipo_documento_fe,
            co_iva.percentuale,
            idiva,
            desc_iva AS descrizione,
            SUM((vb_righe_venditabanco.iva)) as iva,
            SUM((vb_righe_venditabanco.subtotale)) as subtotale,
            SUM((subtotale - sconto + iva)) as totale,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.codice AS codice_anagrafica
        FROM vb_venditabanco
            INNER JOIN vb_righe_venditabanco ON vb_venditabanco.id = vb_righe_venditabanco.idvendita
            INNER JOIN vb_stati_vendita ON vb_venditabanco.idstato = vb_stati_vendita.id
            INNER JOIN co_iva ON vb_righe_venditabanco.idiva = co_iva.id
            LEFT JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = vb_venditabanco.idanagrafica
        WHERE
            vb_venditabanco.data >= '.prepare($date_start . ' 00:00:00').'
            AND vb_venditabanco.data <= '.prepare($date_end . ' 23:59:59').'
            AND vb_stati_vendita.descrizione = "Pagato"
            GROUP BY idiva, vb_venditabanco.id
            ORDER BY numero, data_registrazione';
}

else {
    $query = '
        SELECT
            co_documenti.id,
            co_documenti.data_registrazione,
            co_documenti.data,
            IF(numero = "", numero_esterno, numero) AS numero,
            co_tipidocumento.codice_tipo_documento_fe,
            co_iva.percentuale,
            idiva,
            desc_iva AS descrizione,
            SUM(((iva+iva_rivalsainps)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS iva,
            SUM(((subtotale-sconto)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS subtotale,
            SUM(((subtotale-sconto+iva+co_righe_documenti.rivalsainps-co_righe_documenti.ritenutaacconto)*(IF(co_tipidocumento.reversed = 0, 1,-1 )))) AS totale,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.codice AS codice_anagrafica
        FROM co_righe_documenti
            INNER JOIN co_documenti ON co_documenti.id = co_righe_documenti.iddocumento
            INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_documenti.idanagrafica
            INNER JOIN co_iva ON co_righe_documenti.idiva = co_iva.id
        WHERE idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata")
            and dir = '.prepare($dir).'
            AND is_descrizione = 0
            AND co_documenti.data_competenza >= '.prepare($date_start).'
            AND co_documenti.data_competenza <= '.prepare($date_end).'
            AND '.((!empty($id_sezionale)) ? 'co_documenti.id_segment = '.prepare($id_sezionale).'' : '1=1').'
            GROUP BY idiva, co_documenti.id
        ORDER BY numero, data_registrazione';
}
error_log($query);
$records = $dbo->fetchArray($query);

if (empty(get('notdefinitiva'))) {
    $page = $dbo->fetchOne('SELECT first_page FROM co_stampecontabili WHERE dir='.prepare(filter('dir')).' AND  date_start='.prepare(filter('date_start')).' AND date_end='.prepare(filter('date_end')))['first_page'];
}

// Sostituzioni specifiche
$custom = [
    'tipo' => $tipo,
];
