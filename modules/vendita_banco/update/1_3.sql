-- Creazione nuova tabella per le righe di vendita al banco
CREATE TABLE `vb_righe_venditabanco` (
    `id` int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `idvendita` int NOT NULL,
    `idarticolo` int NOT NULL,
    `is_descrizione` tinyint(1) NOT NULL,
    `is_sconto` tinyint(1) NOT NULL DEFAULT '0',
    `idiva` int NOT NULL,
    `desc_iva` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
    `descrizione` text COLLATE utf8mb4_general_ci NOT NULL,
    `um` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `qta` decimal(15,6) NOT NULL,
    `qta_evasa` decimal(15,6) NOT NULL,
    `prezzo_unitario` decimal(15,6) NOT NULL,
    `iva_unitaria` decimal(17,8) NOT NULL,
    `prezzo_unitario_ivato` decimal(15,6) NOT NULL,
    `sconto_unitario` decimal(15,6) NOT NULL,
    `sconto_iva_unitario` decimal(15,6) NOT NULL,
    `sconto_unitario_ivato` decimal(15,6) NOT NULL,
    `sconto_percentuale` decimal(15,6) NOT NULL,
    `iva` decimal(17,8) NOT NULL,
    `subtotale` decimal(15,6) NOT NULL,
    `sconto` decimal(15,6) NOT NULL,
    `tipo_sconto` enum('UNT','PRC') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'UNT',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `abilita_serial` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB;

-- Importazione righe precedenti
INSERT INTO `vb_righe_venditabanco` SELECT NULL,
        `righe`.`idvendita`,
        `righe`.`idarticolo`,
        0,
        0,
        `righe`.`idiva`,
        `co_iva`.`descrizione`,
        `righe`.`descrizione`,
        `righe`.`um`,
        `righe`.`qta_reale`,
        0,
        `righe`.`prezzo_unitario`,
        (`co_iva`.`percentuale` * `righe`.`prezzo_unitario` / 100),
        (`righe`.`prezzo_unitario` + `co_iva`.`percentuale` * `righe`.`prezzo_unitario` / 100),
        `righe`.`sconto_unitario`,
        (`co_iva`.`percentuale` * `righe`.`sconto_unitario` / 100),
        (`righe`.`sconto_unitario` + `co_iva`.`percentuale` *`righe`.`sconto_unitario` / 100),
        `righe`.`sconto_percentuale`,
        (`co_iva`.`percentuale` * `righe`.`prezzo_unitario` / 100) * `righe`.`qta_reale`,
        `righe`.`prezzo_unitario` * `righe`.`qta_reale`,
        `righe`.`sconto_unitario` * `righe`.`qta_reale`,
        `righe`.`tipo_sconto`,
        NOW(),
        NOW(),
        0
    FROM (SELECT *,
             COUNT(`qta`) AS qta_reale,
             `subtotale` AS prezzo_unitario,
             IF(tipo_sconto = 'PRC', `sconto`, 0) AS sconto_percentuale,
             IF(tipo_sconto = 'PRC', `sconto` * `subtotale` / 100, `sconto`) AS sconto_unitario
        FROM `vb_venditabanco_righe`
            GROUP BY `idvendita`, `idarticolo`
    ) `righe`
        LEFT JOIN `co_iva` ON `co_iva`.`id` =`righe`.`idiva`;

-- Aggiunta campi created_at e updated_at
ALTER TABLE `vb_venditabanco` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `vb_venditabanco` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `vb_stati_vendita` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `vb_stati_vendita` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE `vb_venditabanco_movimenti` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `vb_venditabanco_movimenti` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Aggiornamento query del modulo
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `vb_venditabanco`
    INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
    LEFT JOIN (
        SELECT `idvendita`,
            GROUP_CONCAT(`descrizione` SEPARATOR '', '') AS `descrizione_righe`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `vb_righe_venditabanco`
        GROUP BY `idvendita`
    ) AS righe ON `vb_venditabanco`.`id` = `righe`.`idvendita`
WHERE deleted_at IS NULL AND 1=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Vendita al banco';

UPDATE `zz_views` SET `query` = 'righe.descrizione_righe', `name`='Contenuto' WHERE `name`='Articoli' AND `id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Vendita al banco');

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `name`='Totale ivato' AND `id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Vendita al banco');

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `name`='Imponibile' AND `id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Vendita al banco');

-- Aggiunta costo righe per calcolo margine --
ALTER TABLE `vb_righe_venditabanco` ADD `costo_unitario` DECIMAL(12,6) NOT NULL DEFAULT '0.000000' AFTER `subtotale`; 
UPDATE`vb_righe_venditabanco` SET `costo_unitario`=(SELECT `mg_articoli`.`prezzo_acquisto` FROM `mg_articoli` WHERE `mg_articoli`.`id`=`vb_righe_venditabanco`.`idarticolo`);

ALTER TABLE `vb_righe_venditabanco` ADD `id_reparto` int(11) DEFAULT NULL, ADD FOREIGN KEY (`id_reparto`) REFERENCES `vb_reparti`(`id`) ON DELETE SET NULL;
