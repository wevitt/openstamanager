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

$v_iva = [];
$v_totale = [];

$query = 'SELECT *,
    co_movimenti.id AS idmovimenti, co_documenti.id AS id,
    IF(numero = "", numero_esterno, numero) AS numero,
    (SELECT IF(co_tipidocumento.reversed=0, SUM(subtotale - sconto), (SUM(subtotale - sconto)*-1)) FROM co_righe_documenti AS righe2 WHERE righe2.iddocumento=co_documenti.id AND righe2.idiva=co_righe_documenti.idiva GROUP BY iddocumento) AS subtotale,
    (SELECT IF(co_tipidocumento.reversed=0, SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto), (SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto)*-1)) FROM co_righe_documenti AS righe2 WHERE righe2.iddocumento=co_documenti.id AND righe2.idiva=co_righe_documenti.idiva GROUP BY iddocumento) AS totale,
    (SELECT IF(co_tipidocumento.reversed=0, SUM(iva+iva_rivalsainps), (SUM(iva+iva_rivalsainps)*-1)) FROM co_righe_documenti AS righe2 WHERE righe2.iddocumento=co_documenti.id AND righe2.idiva=co_righe_documenti.idiva GROUP BY iddocumento) AS iva,
    an_anagrafiche.ragione_sociale,
    an_anagrafiche.codice AS codice_anagrafica
FROM co_movimenti
    INNER JOIN co_documenti ON co_movimenti.iddocumento=co_documenti.id
    INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
    INNER JOIN co_iva ON co_righe_documenti.idiva=co_iva.id
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_documenti.idanagrafica
WHERE dir = '.prepare($dir).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND '.((!empty($id_sezionale)) ? 'co_documenti.id_segment = '.prepare($id_sezionale).'' : '1=1').'
GROUP BY co_documenti.id, co_righe_documenti.idiva
ORDER BY CAST( IF(dir="entrata", co_documenti.numero_esterno, co_documenti.numero) AS UNSIGNED)';
$records = $dbo->fetchArray($query);

if (empty(get('notdefinitiva'))) {
    $page = $dbo->fetchOne('SELECT first_page FROM co_stampecontabili WHERE dir='.prepare(filter('dir')).' AND  date_start='.prepare(filter('date_start')).' AND date_end='.prepare(filter('date_end')))['first_page'];
}

// Sostituzioni specifiche
$custom = [
    'tipo' => $tipo,
];
