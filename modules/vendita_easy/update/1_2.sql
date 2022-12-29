ALTER TABLE `vb_venditabanco` ADD `importo_pagato` DECIMAL(15, 6);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Metodi di pagamento standard', 'Contanti,Bonifico bancario,Carta di credito', 'string', '1', 'Vendite', NULL, NULL);