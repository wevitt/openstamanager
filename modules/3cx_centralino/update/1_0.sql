INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, '3cx', 'retrieve', 'contact-lookup', 'Centralino3CX\\API\\ContactLookup', '1'),
(NULL, '3cx', 'create', 'contact-creation', 'Centralino3CX\\API\\ContactCreation', '1'),
(NULL, '3cx', 'create', 'call-journaling', 'Centralino3CX\\API\\CallJournaling', '1');

-- Creazione tabelle relative
CREATE TABLE IF NOT EXISTS `3cx_chiamate` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `numero` varchar(255) NOT NULL,
    `id_anagrafica` INT(11),
    `id_intervento` INT(11),
    `interno` varchar(255) NOT NULL,
    `tipo` varchar(255) NOT NULL,
    `oggetto` varchar(255) NOT NULL,
    `descrizione` varchar(500) NOT NULL,
    `inizio` TIMESTAMP NULL DEFAULT NULL,
    `fine` TIMESTAMP NULL DEFAULT NULL,
    `durata` INT(11) NOT NULL,
    `durata_visibile` varchar(255) NOT NULL,
    `is_entrata` BOOLEAN NOT NULL,
    `is_risposta` BOOLEAN NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE SET NULL,
    FOREIGN KEY (`id_intervento`) REFERENCES `in_interventi`(`id`) ON DELETE SET NULL,
    PRIMARY KEY(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `3cx_lookup` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_anagrafica` INT(11),
    `numero` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`),
    PRIMARY KEY(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `3cx_operatori` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `interno` varchar(255) NOT NULL,
    `id_anagrafica` INT(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`),
    PRIMARY KEY(`id`)
) ENGINE=InnoDB;

-- Aggiunta elementi per la vista principale
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'id', '3cx_chiamate.id', 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Cliente', 'an_anagrafiche.ragione_sociale', 2, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Numero', '3cx_chiamate.numero', 3, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Durata', '3cx_chiamate.durata_visibile', 4, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Descrizione', '3cx_chiamate.descrizione', 5, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Operatore', 'operatore.ragione_sociale', 6, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), '_link_module_', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 7, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), '_link_record_', 'IFNULL(3cx_chiamate.id_intervento, -1)', 8, 0, 0);

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Operatori 3CX'), 'id', '3cx_operatori.id', 1, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Operatori 3CX'), 'Interno', '3cx_operatori.interno', 2, 1, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Operatori 3CX'), 'Operatore', 'an_anagrafiche.ragione_sociale', 2, 1, 0);

INSERT INTO `zz_group_module` (`id`, `idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Tecnici'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Centralino 3CX'), 'Filtro chiamate dell''operatore corrente', 'operatore.idanagrafica=|id_anagrafica|', 'WHR', '1', '1');
