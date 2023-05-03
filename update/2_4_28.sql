-- Aggiunto help per impostazione
UPDATE `zz_settings` SET `help` = 'Documenti di Vendita quali Fatture, DDT e Attività' WHERE `zz_settings`.`nome` = 'Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita'; 

-- Aggiunto flag calcola km
ALTER TABLE `in_tipiintervento` ADD `calcola_km` TINYINT NOT NULL AFTER `costo_diritto_chiamata_tecnico`;
UPDATE `in_tipiintervento` SET `calcola_km`=1;

-- Aggiunto colonna email in Attività
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_interventi`\nINNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`\nLEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`\nLEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`\nLEFT JOIN (\n    SELECT an_sedi.id, CONCAT(an_sedi.nomesede, \'<br>\',an_sedi.telefono, \'<br>\',an_sedi.cellulare,\'<br>\',an_sedi.citta, \' - \', an_sedi.indirizzo) AS info FROM an_sedi\n) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione\nLEFT JOIN (\n    SELECT co_righe_documenti.idintervento, CONCAT(\'Fatt. \', co_documenti.numero_esterno, \' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento\n) AS fattura ON fattura.idintervento = in_interventi.id\nLEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\n    FROM `zz_operations`\n    INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\n    INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\n    INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` \n    WHERE `zz_modules`.`name` = \'Interventi\' AND `zz_operations`.`op` = \'send-email\' \n    GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id\nWHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)|\nGROUP BY `in_interventi`.`id`\nHAVING 2=2\nORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 18, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'icon_title_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'Inviata via email\', \'\')', 19, 1, 0, 0, '', '', 0, 0, 0);

-- Rimozione delle aliquote iva eliminate dalla lista
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_iva` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `name` = 'IVA';

-- Aggiunta colonna Codice in Combinazioni
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Combinazioni'), 'Codice', 'mg_combinazioni.codice', 1, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiunta impostazione per impostare o meno il riferimento del documento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Aggiungi riferimento tra documenti', '1', 'boolean', '1', 'Generali', '18', 'Permette l''aggiunta del riferimento al documento nella descrizione della riga importata');

-- Aggiunto controllo sul widget fatturato per non conteggiare le fatture in Bozza
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\n INNER JOIN co_statidocumento ON co_documenti.idstatodocumento = co_statidocumento.id\nWHERE co_statidocumento.descrizione!=\'Bozza\' AND co_tipidocumento.dir=\'entrata\' |segment| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';

-- Spostamento stampe contabili
UPDATE `zz_prints` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name`='Stampe contabili') WHERE `zz_prints`.`name` = 'Mastrino';
UPDATE `zz_prints` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name`='Stampe contabili') WHERE `zz_prints`.`name` = 'Bilancio';

-- Aggiunta stampa libro giornale
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Stampe contabili'), '1', 'Libro giornale', 'Libro giornale', 'Libro giornale', 'libro_giornale', 'idconto', '', 'fa fa-print', '', '', '0', '0', '1', '1');

-- Aggiunta tabella mansioni
CREATE TABLE IF NOT EXISTS `an_mansioni` ( 
    `id` INT NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`)
);

INSERT INTO `an_mansioni`(
    `nome`
)(
    SELECT DISTINCT `mansione` FROM `an_referenti`
);
ALTER TABLE `an_referenti` ADD `idmansione` INT NOT NULL AFTER `idsede`;

UPDATE `an_referenti`, `an_mansioni` SET `idmansione`=`an_mansioni`.`id` WHERE `an_mansioni`.`nome`=`an_referenti`.`mansione`;

ALTER TABLE `an_referenti` DROP `mansione`;

-- Aggiunto modulo mansioni referenti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
(NULL, 'Mansioni referenti', 'Mansioni referenti', 'mansioni', 'SELECT |select| FROM an_mansioni ORDER BY `nome`', NULL, 'fa fa-angle-right', '1.0', '2.*', '100', (SELECT `id` FROM `zz_modules` AS t WHERE `name`='Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Mansioni referenti'), 'id', 'an_mansioni.id', 1, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Mansioni referenti'), 'Nome', 'an_mansioni.nome', 2, 1, 0, 1);

UPDATE `zz_plugins` SET `options` = '	{ \"main_query\": [	{	\"type\": \"table\", \"fields\": \"Nominativo, Mansione, Telefono, Indirizzo email, Sede\",	\"query\": \"SELECT an_referenti.id, an_referenti.nome AS Nominativo, an_mansioni.nome AS Mansione, an_referenti.telefono AS Telefono, an_referenti.email AS \'Indirizzo email\', IF(idsede = 0, \'Sede legale\', an_sedi.nomesede) AS Sede FROM an_referenti LEFT OUTER JOIN an_sedi ON idsede = an_sedi.id LEFT OUTER JOIN an_mansioni ON idmansione = an_mansioni.id WHERE 1=1 AND an_referenti.idanagrafica=|id_parent| HAVING 2=2 ORDER BY an_referenti.id DESC\"}	]}' WHERE `zz_plugins`.`name` = 'Referenti';

CREATE TABLE IF NOT EXISTS `em_mansioni_template` ( 
    `id` INT NOT NULL AUTO_INCREMENT,
    `idmansione` INT NOT NULL,
    `id_template` INT NOT NULL,
    PRIMARY KEY (`id`)
);

-- Aggiunta notifiche ai tecnici assegnati
ALTER TABLE `in_statiintervento` CHANGE `notifica_tecnici` `notifica_tecnico_sessione` TINYINT(4) NOT NULL; 

ALTER TABLE `in_statiintervento` ADD `notifica_tecnico_assegnato` TINYINT(4) NOT NULL AFTER `notifica_tecnico_sessione`; 

UPDATE `in_statiintervento` SET `notifica_tecnico_assegnato`=`notifica_tecnico_sessione`;

UPDATE `zz_settings` SET `nome` = 'Notifica al tecnico l''aggiunta della sessione nell''attività', `order`=15 WHERE `zz_settings`.`nome` = 'Notifica al tecnico l''assegnazione all''attività';

UPDATE `zz_settings` SET `nome` = 'Notifica al tecnico la rimozione della sessione dall''attività', `order`=16 WHERE `zz_settings`.`nome` = 'Notifica al tecnico la rimozione dall''attività';

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico l''assegnazione all''attività', '0', 'boolean', '1', 'Attività', 17, 'Notifica via email al tecnico l''assegnazione di una nuova attività  (l''indirizzo email deve essere specificato nella sua anagrafica)');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico la rimozione dell''assegnazione dall''attività', '0', 'boolean', '1', 'Attività', 18, 'Notifica via email al tecnico la rimozione dell''assegnazione dall''attività (l''indirizzo email deve essere specificato nella sua anagrafica)');

UPDATE `zz_settings` SET `valore` = '2' WHERE `zz_settings`.`nome` = 'Numero massimo di tentativi';

-- Cambio segno subtotale e iva per righe negative
UPDATE `co_righe_documenti` SET `subtotale`=-`subtotale` WHERE `subtotale`>0 AND `prezzo_unitario`<0;
UPDATE `co_righe_documenti` SET `iva`=-`iva` WHERE `iva`>0 AND `iva_unitaria`<0;

-- Modifica impostazione eliminazione automatica coda d'invio
UPDATE `zz_settings` SET `help` = 'L''impostazione è valida solamente per l''eliminazione della coda d''invio delle newsletter.' WHERE `zz_settings`.`nome` = 'Numero di giorni mantenimento coda di invio';

-- Migliorie modulo Coda di invio
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Tutte', '1=1', 'WHR', '####', '', 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Non inviate', '1=1 AND sent_at IS NULL', 'WHR', '####', '', 0, 0, 0, 0);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Tentativi', '`em_emails`.`attempt`', 7, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Data creazione', '`em_emails`.`created_at`', 9, 1, 0, 1, '', '', 1, 0, 0);
UPDATE `zz_views` SET `format` = '0' WHERE `zz_views`.`name` = 'Utente' AND id_module=(SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'); 

-- Aggiunto plugin Movimenti in fatture di acquisto, vendita e anagrafiche
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Movimenti contabili', 'Movimenti contabili', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'tab', '', '1', '1', '0', '', '', NULL, 'custom', 'movimenti_contabili', '');
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Movimenti contabili', 'Movimenti contabili', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'tab', '', '1', '1', '0', '', '', NULL, 'custom', 'movimenti_contabili', '');
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Movimenti contabili', 'Movimenti contabili', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'tab', '', '1', '1', '0', '', '', NULL, 'custom', 'movimenti_contabili', '');

-- Aggiunto plugin Presentazioni bancarie
ALTER TABLE `co_scadenziario` ADD `presentazioni_exported_at` TIMESTAMP NULL DEFAULT NULL;

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Presentazioni Bancarie', 'Presentazioni Bancarie', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'tab_main', '', '1', '1', '0', '', '', NULL, 'custom', 'presentazioni_bancarie', '');

-- Aggiunte note interne in template email
ALTER TABLE `em_templates` ADD `note_aggiuntive` TEXT NOT NULL AFTER `predefined`;

-- Eliminazione modulo gestione componenti
DELETE FROM `zz_modules` WHERE `zz_modules`.`name` = 'Gestione componenti';

-- Aggiunta distinta scadenze
ALTER TABLE `co_scadenziario` ADD `distinta` VARCHAR(255) NULL AFTER `presentazioni_exported_at`;
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Distinta', 'co_scadenziario.distinta', 15, 1, 0, 0, '', '', 0, 0, 1);

-- Aggiunte note interne in Contratti e Preventivi
ALTER TABLE `co_preventivi` ADD `informazioniaggiuntive` TEXT NULL DEFAULT NULL AFTER `sconto_finale_percentuale`; 
ALTER TABLE `co_contratti` ADD `informazioniaggiuntive` TEXT NULL DEFAULT NULL AFTER `sconto_finale_percentuale`;