UPDATE `zz_modules` SET `options` = "SELECT
|select|
FROM
`mg_articoli`
LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
LEFT JOIN (SELECT SUM(qta - qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 0) GROUP BY idarticolo) ordini ON ordini.idarticolo = mg_articoli.id
LEFT JOIN (SELECT `idarticolo`, `idsede`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `idsede` = |giacenze_sedi_idsede| GROUP BY `idarticolo`, `idsede`) movimenti ON `mg_articoli`.`id` = `movimenti`.`idarticolo`
LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS categoria ON categoria.id= mg_articoli.id_categoria
LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS sottocategoria ON sottocategoria.id=mg_articoli.id_sottocategoria
LEFT JOIN (SELECT co_iva.percentuale AS perc, co_iva.id, zz_settings.nome FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore)AS iva ON iva.nome= 'Iva predefinita'
WHERE
1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING
2=2 AND `Q.tà` > 0
ORDER BY
`descrizione`" WHERE `name` = 'Giacenze sedi';


INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`, `created_at`, `updated_at`) VALUES
(52,	'Articoli da evadere',	'Articoli da evadere',	24,	24,	'tab_main',	'',	1,	1,	0,	'2.*',	'0.1',	NULL,	' {\"main_query\": [ { \"type\": \"table\", \"fields\": \"Cliente, Numero ordine, Descrizione, Qta ordinata, Qta evasa, Qta da evadere, Data ordine\", \"query\": \"SELECT or_ordini.idstatoordine, concat(an_anagrafiche.codice, \' - \', an_anagrafiche.ragione_sociale) as Cliente, or_ordini.numero as \'Numero ordine\', or_righe_ordini.idarticolo, mg_articoli.descrizione as Descrizione, \nor_righe_ordini.qta as \'Qta ordinata\', or_righe_ordini.qta_evasa as \'Qta evasa\', (or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS \'Qta da evadere\', data as \'Data ordine\' FROM or_righe_ordini INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine INNER JOIN mg_articoli ON mg_articoli.id = or_righe_ordini.idarticolo INNER JOIN an_anagrafiche ON or_ordini.idanagrafica = an_anagrafiche.idanagrafica WHERE ((or_righe_ordini.qta - or_righe_ordini.qta_evasa) > 0) AND or_ordini.idstatoordine = 7 AND or_ordini.idtipoordine = 2\"} ]}\n\n',	'',	'',	'2022-12-21 14:51:14',	'2023-01-23 12:25:23');
