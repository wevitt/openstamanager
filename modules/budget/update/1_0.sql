-- Tabella con importi previsionali --
CREATE TABLE `bu_previsionale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codice` varchar(255) DEFAULT NULL,
  `id_conto` int(11) DEFAULT NULL,
  `id_anagrafica` int(11) DEFAULT NULL,
  `id_movimento` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `tipo` varchar(7) DEFAULT NULL,
  `sezione` ENUM('economico','finanziario','economico_finanziario') NOT NULL,
  `descrizione` varchar(255) NULL DEFAULT NULL,
  `concluso` TINYINT(2) NOT NULL DEFAULT '0',
  `totale` decimal(12,6) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB;

-- Tabella per sorgenti esterne dei dati --
CREATE TABLE `bu_sorgenti` ( 
  `id` INT NOT NULL AUTO_INCREMENT , 
  `nome` VARCHAR(255) NULL DEFAULT NULL , 
  `sezionale` VARCHAR(255) NULL DEFAULT NULL , 
  `query` TEXT NULL DEFAULT NULL , 
  `query2` TEXT NULL DEFAULT NULL , 
  `enabled` TINYINT NOT NULL DEFAULT '1' , 
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  PRIMARY KEY (`id`)
) ENGINE = InnoDB; 

-- Aggiunta viste per il modulo gestione sorgenti esterne --
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Sorgenti esterne' ), 'id', 'bu_sorgenti.id', '0', '1', '0', '0', '', '', '0', '0', '0');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('1', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('2', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('3', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('4', (SELECT MAX(`id`) FROM `zz_views`) ); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Sorgenti esterne' ), 'Nome', 'bu_sorgenti.nome', '1', '1', '0', '0', '', '', '1', '0', '0');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('1', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('2', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('3', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('4', (SELECT MAX(`id`) FROM `zz_views`) ); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Sorgenti esterne' ), 'Tipologia', 'bu_sorgenti.sezionale', '2', '1', '0', '0', '', '', '1', '0', '0');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('1', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('2', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('3', (SELECT MAX(`id`) FROM `zz_views`) ); 
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES ('4', (SELECT MAX(`id`) FROM `zz_views`) ); 

INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES
(NULL, 'Redditività', 'stats', (SELECT id FROM zz_modules WHERE name='Budget'), 'controller_top', 'col-md-12', 'SELECT CONCAT(FORMAT(ABS((SELECT SUM(-totale) AS ricavi FROM co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 WHERE co_pianodeiconti2.dir = "uscita" AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND primanota=0)/(SELECT SUM(-totale) AS utile FROM co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 WHERE co_pianodeiconti2.dir IN( "entrata", "uscita" ) AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND primanota=0)*100), 2), ''%'') AS dato', '#f2bd00', 'fa fa-line-chart', '', '', '', '', 'Redditività', 1, 1, NULL);


