CREATE TABLE IF NOT EXISTS `3cx_push` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_utente` int(11) NOT NULL,
    `params` TEXT NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`id`)
) ENGINE=InnoDB;

ALTER TABLE `3cx_chiamate` ADD `id_tecnico` INT(11) NULL AFTER `id_intervento`,
    CHANGE `interno` `interno` varchar(255),
    CHANGE `tipo` `tipo` varchar(255),
    CHANGE `oggetto` `oggetto` varchar(255),
    CHANGE `descrizione` `descrizione` TEXT,
    CHANGE `durata` `durata` INT(11),
    CHANGE `durata_visibile` `durata_visibile` varchar(255),
    ADD `in_entrata` BOOLEAN NOT NULL,
    ADD `is_gestito` BOOLEAN NOT NULL,
    ADD `id_sede` INT(11),
    ADD `id_referente` INT(11),
    ADD `numero_journaling` varchar(255) AFTER `numero`,
    ADD `numero_lookup` varchar(255) AFTER `numero`,
    ADD FOREIGN KEY (`id_tecnico`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`id_sede`) REFERENCES `an_sedi`(`id`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`id_referente`) REFERENCES `an_referenti`(`id`) ON DELETE SET NULL;

-- Aggiornamento query modulo
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM 3cx_chiamate LEFT JOIN an_anagrafiche ON 3cx_chiamate.id_anagrafica = an_anagrafiche.idanagrafica LEFT JOIN an_anagrafiche AS tecnico ON 3cx_chiamate.id_tecnico = tecnico.idanagrafica AND 1=1 HAVING 2=2 ORDER BY id DESC' WHERE `name` = 'Centralino 3CX';

UPDATE `zz_views` SET `query` = 'tecnico.ragione_sociale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX') AND `name` = 'Operatore';
DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX') AND `name` = '_link_module_';
DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX') AND `name` = '_link_record_';
UPDATE `zz_views` SET `query` = 'IFNULL(3cx_chiamate.descrizione, 3cx_chiamate.oggetto)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX') AND `name` = 'Descrizione';

-- Aggiunta colonna di tipo
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'icon_Tipo', 'IF(3cx_chiamate.in_entrata, ''fa fa-download'', ''fa fa-upload'')', 9, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'icon_title_Tipo', 'IF(3cx_chiamate.in_entrata, ''In entrata'', ''In uscita'')', 9, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'icon_Risp.', 'IF(3cx_chiamate.is_risposta, ''fa fa-phone'', ''fa fa-times-circle'')', 10, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'icon_title_Risp.', 'IF(3cx_chiamate.is_risposta, ''Risposta'', ''Senza risposta'')', 10, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Orario', '3cx_chiamate.inizio', 3, 1, 1);

-- Fix gestione call lookup
UPDATE `zz_api_resources` SET `type` = 'create' WHERE `resource` = 'contact-lookup';
DROP TABLE `3cx_lookup`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Operatori 3CX' AND `t2`.`name` = 'Centralino 3CX') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, '3cx', 'create', 'call-notification', 'Centralino3CX\\API\\CallNotification', '1');
