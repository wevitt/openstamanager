CREATE TABLE `ac_acconti` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `idanagrafica` int NULL,
  `idordine` int NULL COMMENT 'se idordine non presente allora l\'acconto non viene fatto sull\'anagrafica',
  `importo` float NOT NULL
);

CREATE TABLE `ac_acconti_righe` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `idacconto` int NOT NULL,
  `idfattura` int NULL,
  `importo_fatturato` float NOT NULL,
  `tipologia` varchar(30) NULL
);

ALTER TABLE `ac_acconti_righe`
CHANGE `idfattura` `idfattura` int(11) NOT NULL AFTER `idacconto`,
ADD `idriga_fattura` int(11) NULL AFTER `idfattura`;
