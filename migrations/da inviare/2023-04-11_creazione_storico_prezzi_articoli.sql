CREATE TABLE `mg_storico_prezzi_articoli` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `idarticolo` int(11) NOT NULL,
  `idutente` int(11) NOT NULL,
  `idfornitore` int(11) NOT NULL,
  `idlistino` int(11) NOT NULL,
  `prezzo` decimal(15,6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE `mg_storico_prezzi_articoli`
ADD INDEX `idarticolo` (`idarticolo`),
ADD INDEX `idfornitore` (`idfornitore`),
ADD INDEX `idlistino` (`idlistino`);

ALTER TABLE `mg_storico_prezzi_articoli`
ADD `tipo_prezzo` varchar(30) NULL AFTER `idlistino`,
CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`
