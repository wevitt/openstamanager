INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'id', '`vb_venditabanco`.`id`', 1, 1, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'Numero', '`vb_venditabanco`.`numero`', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'Totale', '`righe`.`totale`', 3, 1, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'Articoli', '`righe`.`descrizione_righe`', 4, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'Data', '`vb_venditabanco`.`data`', 5, 1, 1, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'icon_Stato', '`vb_stati_vendita`.`icona`', 6, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Easy vendita'), 'icon_title_Stato', '`vb_stati_vendita`.`descrizione`', 6, 1, 0, 1, 0, 0);

-- Aggiunta campi aggiuntivi vendita --
ALTER TABLE `vb_venditabanco` ADD `iddocumento` INT NULL DEFAULT NULL AFTER `idstato` , ADD `data_emissione` TIMESTAMP NULL DEFAULT NULL AFTER `iddocumento`;
ALTER TABLE `vb_venditabanco` ADD CONSTRAINT `vb_venditabanco_ibfk_1` FOREIGN KEY (`iddocumento`) REFERENCES `co_documenti`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION; 