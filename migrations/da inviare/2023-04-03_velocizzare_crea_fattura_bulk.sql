ALTER TABLE `ac_acconti`
ADD INDEX `idanagrafica` (`idanagrafica`),
ADD INDEX `idordine` (`idordine`);

ALTER TABLE `ac_acconti_righe`
ADD INDEX `idacconto` (`idacconto`),
ADD INDEX `idfattura` (`idfattura`),
ADD INDEX `idriga_fattura` (`idriga_fattura`),
ADD INDEX `idiva` (`idiva`);

ALTER TABLE `dt_righe_ddt`
ADD INDEX `idddt` (`idddt`),
ADD INDEX `idordine` (`idordine`),
ADD INDEX `idarticolo` (`idarticolo`),
ADD INDEX `is_descrizione` (`is_descrizione`),
ADD INDEX `is_sconto` (`is_sconto`);

ALTER TABLE `co_righe_documenti`
ADD INDEX `idddt` (`idddt`),
ADD INDEX `is_spesa_incasso` (`is_spesa_incasso`),
ADD INDEX `is_spesa_trasporto` (`is_spesa_trasporto`);
