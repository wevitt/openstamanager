-- Impostazione stampa scontrino con vendita aperta
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Stampa scontrino fiscale con vendita aperta', '1', 'boolean', '1', 'Vendite', NULL, NULL);

-- Aggiunta stampa vendita al banco
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Vendita al banco'), '1', 'Vendita al banco', 'Vendita al banco', 'Vendita al banco', 'vendita_banco', '', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Aggiunte API resource
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'v1', 'retrieve', 'vendita_banco', 'Modules\\VenditaBanco\\API\\v1\\Vendite', '1');

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'v1', 'retrieve', 'righe_vendita_banco', 'Modules\\VenditaBanco\\API\\v1\\Righe', '1');

-- Modulo Statistiche vendite
INSERT INTO `zz_modules` (`name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES
('Statistiche vendite', 'Statistiche vendite', 'vendita_banco', 'SELECT |select|\r\nFROM `vb_venditabanco`\r\nINNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`\r\nLEFT JOIN (SELECT idvendita, idarticolo, vb_righe_venditabanco.descrizione, SUM(subtotale-sconto) AS importo, SUM(qta) AS qta, vb_reparti.descrizione AS reparto FROM vb_righe_venditabanco LEFT JOIN vb_reparti ON vb_righe_venditabanco.id_reparto=vb_reparti.id GROUP BY idvendita, idarticolo, descrizione) righe ON vb_venditabanco.id=righe.idvendita\r\nLEFT JOIN mg_articoli ON righe.idarticolo=mg_articoli.id\r\nLEFT JOIN mg_categorie ON mg_articoli.id_categoria=mg_categorie.id\r\nLEFT JOIN `an_anagrafiche` ON `vb_venditabanco`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\nWHERE vb_venditabanco.deleted_at IS NULL AND vb_venditabanco.data >= \'|period_start|\' AND vb_venditabanco.data <= \'|period_end|\' AND 1=1 HAVING 2=2 ORDER BY vb_venditabanco.data', '', 'fa fa-angle-right', '1.0', '2.*', 0, (SELECT `id` FROM `zz_modules` AS t WHERE name='Vendita al banco'), 0, 1, 0, 0);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'id', 'vb_venditabanco.id', 0, 0, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Numero', '`vb_venditabanco`.`numero`', 1, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Cliente', 'an_anagrafiche.ragione_sociale', 3, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Data', '`vb_venditabanco`.`data`', 4, 1, 0, 1, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'icon_Stato', '`vb_stati_vendita`.`icona`', 10, 0, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'icon_title_Stato', '`vb_stati_vendita`.`descrizione`', 11, 0, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Riga', 'IF(mg_articoli.codice IS NULL, righe.descrizione, CONCAT(mg_articoli.codice, \' - \', righe.descrizione))', 5, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Reparto', 'reparto', 6, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Prezzo', 'righe.importo/righe.qta', 7, 1, 0, 1, '', '', 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Totale', 'righe.importo', 9, 1, 0, 1, '', '', 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Statistiche vendite'), 'Q.tà', 'righe.qta', 8, 1, 0, 1, '', '', 1, 1, 0);

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (
SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Statistiche vendite');

-- Aggiornamento parent su moduli installati prima
UPDATE `zz_modules` `sottomoduli` INNER JOIN `zz_modules` `moduli` ON (`sottomoduli`.`name` IN('Reparti', 'Tipologia pagamenti') AND `moduli`.`name` = 'Vendita al banco') SET `sottomoduli`.`parent` = `moduli`.`id`;
