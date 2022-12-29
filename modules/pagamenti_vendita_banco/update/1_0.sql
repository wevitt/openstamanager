-- Aggiunta tipologia per IVA
ALTER TABLE `co_pagamenti` ADD `tipo_xon_xoff` varchar(255) DEFAULT NULL;
UPDATE `co_pagamenti` SET `tipo_xon_xoff` = '1T';
UPDATE `co_pagamenti` SET `tipo_xon_xoff` = '3T' WHERE `descrizione`='Visa';

-- Creazione delle Viste per il modulo
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologia pagamenti'), 'id', '`tipo_xon_xoff`', 1, 1, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologia pagamenti'), 'Tipologia', '`tipo_xon_xoff`', 2, 1, 0, 1, 1, 0);

UPDATE `zz_settings` SET `tipo` = 'query=SELECT id, CONCAT(descrizione, IF(tipo_xon_xoff, CONCAT(\' ( \', tipo_xon_xoff, \' )\'), \'\')) AS descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL ORDER BY descrizione' WHERE `zz_settings`.`nome` = 'Pagamento predefinito'; 