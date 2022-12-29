-- Aggiunta tipologia per IVA
CREATE TABLE IF NOT EXISTS `vb_reparti` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codice` varchar(20) NOT NULL,
    `descrizione` varchar(255) NOT NULL,
    `is_servizio` BOOLEAN NOT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `vb_reparti` (`codice`, `descrizione`, `is_servizio`) VALUES
('1R', 'Reparto 1: Beni', 0),
('2R', 'Reparto 2: Servizi', 1),
('3R', 'Reparto 3: Altro', 0);

ALTER TABLE `mg_articoli` ADD `id_reparto` int(11) DEFAULT NULL, ADD FOREIGN KEY (`id_reparto`) REFERENCES `vb_reparti`(`id`) ON DELETE SET NULL;
ALTER TABLE `mg_categorie` ADD `id_reparto` int(11) DEFAULT NULL, ADD FOREIGN KEY (`id_reparto`) REFERENCES `vb_reparti`(`id`) ON DELETE SET NULL;

-- UPDATE `mg_articoli`
--     INNER JOIN `co_iva` ON `co_iva`.`id` = `mg_articoli`.`idiva_vendita`
--     INNER JOIN `vb_reparti` ON `co_iva`.`tipo_xon_xoff` = `vb_reparti`.`codice`
--     SET `mg_articoli`.`id_reparto` = `vb_reparti`.`id`;

-- ALTER TABLE `co_iva` DROP `tipo_xon_xoff`;

UPDATE `mg_articoli` SET `mg_articoli`.`id_reparto` = IF(`mg_articoli`.`servizio`, 2, 1);

-- Impostazioni per i tecnici assegnati delle Attività
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Reparto predefinito', '1', 'query=SELECT id, CONCAT(codice, " - ", descrizione) AS descrizione FROM vb_reparti', '1', 'Vendite', 1);

-- Creazione delle Viste per il modulo
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Reparti'), 'id', '`id`', 1, 1, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Reparti'), 'Codice', '`codice`', 1, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Reparti'), 'Descrizione', '`descrizione`', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Reparto', '(SELECT vb_reparti.codice FROM vb_reparti WHERE vb_reparti.id = mg_articoli.id_reparto)', 20, 1, 0, 1, 1, 0);

-- Disattivazione modulo Tipologia IVA
UPDATE `zz_modules` SET `enabled` = '0' WHERE `zz_modules`.`name` = 'Tipologia IVA';
