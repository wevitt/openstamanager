ALTER TABLE `vb_venditabanco` ADD `idanagrafica` INT(11) AFTER `note`;

-- Aggiunta cliente 
UPDATE `zz_modules` SET `options` = 'SELECT |select|\r\nFROM `vb_venditabanco`\r\n     INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`\r\n     LEFT JOIN (\r\n         SELECT `idvendita`,\r\n                GROUP_CONCAT(`descrizione` SEPARATOR \', \') AS `descrizione_righe`,\r\n                SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\r\n                SUM(`subtotale` - `sconto` + `iva`) AS `totale`\r\n         FROM `vb_righe_venditabanco`\r\n         GROUP BY `idvendita`\r\n     ) AS righe ON `vb_venditabanco`.`id` = `righe`.`idvendita`\r\n     LEFT JOIN `an_anagrafiche` ON `vb_venditabanco`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\nWHERE vb_venditabanco.deleted_at IS NULL AND 1=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Vendita al banco';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ('0', (SELECT `id` FROM `zz_modules` WHERE `name`='Vendita al banco'), 'Cliente', 'an_anagrafiche.ragione_sociale', '2', '1', '0', '0', '', '', '1', '0', '0');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (
SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Vendita al banco' AND `zz_views`.`name`='Cliente'
);
