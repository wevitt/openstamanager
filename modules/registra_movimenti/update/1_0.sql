-- Creata tabella che contiene i movimenti bancari
CREATE TABLE `co_registra_movimenti` ( `id` INT NOT NULL AUTO_INCREMENT , `data` DATE NULL , `descrizione` TEXT NOT NULL , `importo` DECIMAL(15,6) NOT NULL , `processed_at` timestamp NULL DEFAULT NULL , PRIMARY KEY (`id`)); 

-- Aggiunta Prima Nota in import
INSERT INTO `zz_imports` (`id_module`, `name`, `class`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Registra movimenti'), 'Prima nota', 'Modules\\RegistraMovimenti\\Import\\CSV');

-- Aggiunta colonna codice ABI
ALTER TABLE `co_registra_movimenti` ADD `codice_abi` VARCHAR(255) NULL AFTER `importo`;