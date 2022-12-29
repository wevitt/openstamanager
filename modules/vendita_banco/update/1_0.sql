--
-- Struttura della tabella `vb_stati_vendita`
--

CREATE TABLE IF NOT EXISTS `vb_stati_vendita` (
	`id` tinyint(4) NOT NULL AUTO_INCREMENT,
	`descrizione` varchar(100) NOT NULL,
	`icona` varchar(100) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `vb_stati_vendita`
--

INSERT INTO `vb_stati_vendita` (`id`, `descrizione`, `icona`) VALUES
(NULL, 'Aperto', 'fa fa-2x fa-exclamation-circle text-danger'),
(NULL, 'Pagato', 'fa fa-2x fa-check-circle text-success');

--
-- Struttura della tabella `vb_venditabanco`
--

CREATE TABLE IF NOT EXISTS `vb_venditabanco` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`numero` int(11) NOT NULL,
	`data` datetime NOT NULL,
	`idiva` int(11) NOT NULL,
	`idanagrafica` int(11) NOT NULL,
	`idpagamento` int(11) NOT NULL,
	`idconto` int(11) NOT NULL,
	`n_colli` int(11) NOT NULL,
	`note` varchar(255) NOT NULL,
	`idstato` int(11) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

--
-- Struttura della tabella `vb_venditabanco_righe`
--

CREATE TABLE IF NOT EXISTS `vb_venditabanco_righe` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`idvendita` int(11) NOT NULL,
	`idarticolo` int(11) NOT NULL,
	`barcode` varchar(255) NOT NULL,
	`idiva` int(11) NOT NULL,
	`descrizione` text NOT NULL,
	`subtotale` float(10,4) NOT NULL,
	`sconto` float(10,4) NOT NULL,
	`um` varchar(20) NOT NULL,
	`qta` float(10,4) NOT NULL,
	`qta_evasa` float(10,4) NOT NULL,
	`idlistino` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Widget vendita al banco
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL , 'Vendite al banco', 'stats', (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` LIKE "Vendita al banco"), 'controller_top', 'col-md-12', 'SELECT CONCAT_WS( " ", REPLACE( REPLACE( REPLACE( FORMAT (SUM(subtotale),2), ",", "#"), ".", "," ), "#", "."), "&euro;" ) AS dato FROM vb_venditabanco_righe INNER JOIN vb_venditabanco ON  vb_venditabanco_righe.idvendita = vb_venditabanco.id WHERE (CAST(`data` AS DATE) BETWEEN ''|period_start|'' AND ''|period_end|'')', '#4dc347', 'fa fa-money', '', '', '', '', 'Vendite al banco', '1', '1');

-- Impostazioni
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
(NULL , 'Salta automaticamente pop-up inserimento nuova vendita', '1', 'boolean', '1', 'Vendite'),
(NULL , 'Aggiungere automaticamente articolo alla vendita quando selezionato', '1', 'boolean', '1', 'Vendite');

-- Fix
ALTER TABLE `mg_articoli` ADD `provvigione` FLOAT( 5, 2 ) NOT NULL ;
ALTER TABLE `mg_unitamisura` ADD `ordine` TINYINT( 6 ) NOT NULL AFTER `valore` ;
ALTER TABLE `mg_movimenti` ADD `idvendita` int(11) NOT NULL;

-- Aggiunto campo sconto legato articolo
ALTER TABLE `mg_articoli` ADD `sconto` DECIMAL( 12, 4 ) NOT NULL AFTER `provvigione` ;

-- Aggiunta nuovo conto per le vendite al banco
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`) VALUES (NULL, '000100', 'Ricavi vendita al banco', '20', 'entrata');

-- Aggiunta campo data fiscale
ALTER TABLE `vb_venditabanco` ADD `data_fiscale` datetime NOT NULL;

-- Impostazioni per porta e ip stampante fiscale
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
(NULL, 'Indirizzo IP registratore di cassa', '', 'string', '1', 'Vendite'),
(NULL, 'Porta registratore di cassa', '', 'string', '1', 'Vendite');

-- Aggiunta tabella di collegamento tra movimenti e vendite
CREATE TABLE `vb_venditabanco_movimenti` (`idmovimento` int(11) NOT NULL, `idvendita` int(11) NOT NULL);
ALTER TABLE `vb_venditabanco_movimenti` ADD PRIMARY KEY (`idmovimento`,`idvendita`);

-- Pagamento predefinito per le vendite
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES
(NULL , 'Pagamento predefinito', '1', 'query=SELECT id, descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL AND num_giorni = 0 ORDER BY descrizione ASC', '1', 'Vendite', '');

-- Idmagazzino per vendite al banco
ALTER TABLE `vb_venditabanco` ADD `idmagazzino` INT NOT NULL AFTER `n_colli`;

-- Rimozione campi inutilizzati n_colli, idanagrafica e idiva
ALTER TABLE `vb_venditabanco` DROP `n_colli`;
ALTER TABLE `vb_venditabanco` DROP `idanagrafica`;
ALTER TABLE `vb_venditabanco` DROP `idiva`;
