ALTER TABLE `vb_venditabanco_righe` CHANGE `idvendita` `idvendita` INT(11) NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `idiva` `idiva` INT(11) NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `subtotale` `subtotale` decimal(15,6) NOT NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `sconto` `sconto` decimal(15,6) NOT NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `qta` `qta` decimal(15,6) NOT NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `qta_evasa` `qta_evasa` decimal(15,6) NOT NULL;
ALTER TABLE `vb_venditabanco_righe` CHANGE `idlistino` `idlistino` INT(11) NULL;

-- Rimozione campi inutilizzati idlistino
ALTER TABLE `vb_venditabanco_righe` DROP `idlistino`;

-- Aggiunta del campo Tipo sconto sulle righe --
ALTER TABLE `vb_venditabanco_righe` ADD `tipo_sconto` VARCHAR(255) NULL AFTER `qta_evasa`;

-- Modifica modulo
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `vb_venditabanco`\r\n    INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`\r\n    LEFT OUTER JOIN (\r\n        SELECT `vb_venditabanco_righe`.`idvendita`, SUM(`vb_venditabanco_righe`.`subtotale` - IF (`vb_venditabanco_righe`.`tipo_sconto`=\'PRC\', (vb_venditabanco_righe.subtotale*vb_venditabanco_righe.sconto)/100, `vb_venditabanco_righe`.`sconto`)) AS `totale`,\r\n        SUM( ( (`vb_venditabanco_righe`.`subtotale` - IF (`vb_venditabanco_righe`.`tipo_sconto`=\'PRC\', (vb_venditabanco_righe.subtotale*vb_venditabanco_righe.sconto)/100, `vb_venditabanco_righe`.`sconto`))*(SELECT co_iva.percentuale FROM co_iva WHERE co_iva.id=vb_venditabanco_righe.idiva) )/100) AS `totale_iva`,\r\n        GROUP_CONCAT(`mg_articoli`.`descrizione`) AS `articoli` \r\n        FROM `vb_venditabanco_righe` INNER JOIN `mg_articoli` ON  `vb_venditabanco_righe`.`idarticolo` = `mg_articoli`.`id` \r\n        GROUP BY `idvendita`\r\n    ) AS righe ON `vb_venditabanco`.`id` = `righe`.`idvendita`\r\n    WHERE deleted_at IS NULL AND 1=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Vendita al banco';

-- Modifica delle viste
UPDATE `zz_views` SET `name`='Imponibile', `order`=4 WHERE `name`='Totale' AND `id_module`=(SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Vendita al banco');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'Totale ivato', '(`righe`.`totale`+`righe`.`totale_iva`)', 4, 1, 1, 1, 1, 1);

-- Impostazioni
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
(NULL , 'Gestisci articoli sottoscorta', '1', 'boolean', '0', 'Vendite');
