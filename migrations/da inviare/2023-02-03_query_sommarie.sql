-	ALTER TABLE `or_ordini`
	ADD `id_sede_partenza` int(11) NOT NULL AFTER `idreferente`,
	CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-	DROP TABLE IF EXISTS `mg_articoli_sedi`;
	CREATE TABLE `mg_articoli_sedi` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `id_articolo` int(11) NOT NULL,
	  `id_sede` int(11) DEFAULT NULL,
	  `threshold_qta` int(11) NOT NULL COMMENT 'soglia minima',
	  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
	  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

- 	INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`)
	SELECT 'Gestisci soglia minima per magazzino', '1', 'boolean', '1', 'Magazzino', '2022-12-21 14:51:03', '2023-01-05 11:29:48', NULL, NULL
	FROM `zz_settings`
	WHERE ((`id` = '21'));

-	INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES
	(186,	'Conto predefinito di vendita',	'0',	'query=SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, \'.\', co_pianodeiconti3.numero, \' \', co_pianodeiconti3.descrizione ) AS descrizione FROM co_pianodeiconti3 INNER JOIN (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE (co_pianodeiconti2.dir=\'entrata\' OR co_pianodeiconti2.dir=\'entrata uscita\') ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC',	1,	'Magazzino',	'2023-01-12 11:03:05',	'2023-01-12 11:38:51',	1,	NULL),
	(185,	'Conto predefinito di acquisto',	'0',	'query=SELECT co_pianodeiconti3.id, CONCAT( co_pianodeiconti2.numero, \'.\', co_pianodeiconti3.numero, \' \', co_pianodeiconti3.descrizione ) AS descrizione  FROM co_pianodeiconti3 INNER JOIN  (co_pianodeiconti2 INNER JOIN co_pianodeiconti1 ON co_pianodeiconti2.idpianodeiconti1=co_pianodeiconti1.id) ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id  WHERE (co_pianodeiconti2.dir=\'uscita\' OR co_pianodeiconti2.dir=\'entrata/uscita\') ORDER BY co_pianodeiconti2.numero ASC, co_pianodeiconti3.numero ASC',	1,	'Magazzino',	'2023-01-12 11:02:47',	'2023-01-12 11:38:49',	1,	NULL);

- 	UPDATE `zz_modules` SET `options` = "SELECT
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


-	//Quando viene spedita una mail, aggiungere il reply-to all'indirizzo dell'account che sta effettuando l'invio
	ALTER TABLE em_accounts
	ADD force_reply_to tinyint(1) NOT NULL DEFAULT '0' AFTER from_address,
	CHANGE updated_at updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER created_at;




- INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`, `created_at`, `updated_at`) VALUES
(52,	'Articoli da evadere',	'Articoli da evadere',	24,	24,	'tab_main',	'',	1,	1,	0,	'2.*',	'0.1',	NULL,	' {\"main_query\": [ { \"type\": \"table\", \"fields\": \"Cliente, Numero ordine, Descrizione, Qta ordinata, Qta evasa, Qta da evadere, Data ordine\", \"query\": \"SELECT or_ordini.idstatoordine, concat(an_anagrafiche.codice, \' - \', an_anagrafiche.ragione_sociale) as Cliente, or_ordini.numero as \'Numero ordine\', or_righe_ordini.idarticolo, mg_articoli.descrizione as Descrizione, \nor_righe_ordini.qta as \'Qta ordinata\', or_righe_ordini.qta_evasa as \'Qta evasa\', (or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS \'Qta da evadere\', data as \'Data ordine\' FROM or_righe_ordini INNER JOIN or_ordini ON or_ordini.id = or_righe_ordini.idordine INNER JOIN mg_articoli ON mg_articoli.id = or_righe_ordini.idarticolo INNER JOIN an_anagrafiche ON or_ordini.idanagrafica = an_anagrafiche.idanagrafica WHERE ((or_righe_ordini.qta - or_righe_ordini.qta_evasa) > 0) AND or_ordini.idstatoordine = 7 AND or_ordini.idtipoordine = 2\"} ]}\n\n',	'',	'',	'2022-12-21 14:51:14',	'2023-01-23 12:25:23');



- UPDATE `zz_modules` SET
`id` = '16',
`name` = 'Prima nota',
`title` = 'Prima nota',
`directory` = 'primanota',
`options` = 'SELECT\r\n    |select| \r\nFROM\r\n    `co_movimenti`\r\nINNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id`\r\nLEFT JOIN vb_venditabanco ON vb_venditabanco.id = \r\n co_movimenti.iddocumento\r\nLEFT JOIN `co_documenti` ON `co_documenti`.`id` = `co_movimenti`.`iddocumento`\r\nLEFT JOIN `an_anagrafiche` ON `co_movimenti`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica`\r\nWHERE\r\n    1=1 AND `primanota` = 1  |date_period(`co_movimenti`.`data`)|\r\nGROUP BY\r\n    `idmastrino`,\r\n    `primanota`,\r\n    `co_movimenti`.`data`,\r\nvb_venditabanco.numero_esterno,\r\nco_documenti.numero_esterno,\r\n    `co_movimenti`.`descrizione`,\r\n    `an_anagrafiche`.`ragione_sociale`\r\nHAVING\r\n    2=2\r\nORDER BY\r\n    `co_movimenti`.`data`\r\nDESC',
`options2` = '',
`icon` = 'fa fa-angle-right',
`version` = '2.4.39',
`compatibility` = '2.4.39',
`order` = '5',
`parent` = '12',
`default` = '1',
`enabled` = '1',
`created_at` = '2022-12-21 14:50:58',
`updated_at` = now(),
`use_notes` = '0',
`use_checklists` = '0'
WHERE `id` = '16';
