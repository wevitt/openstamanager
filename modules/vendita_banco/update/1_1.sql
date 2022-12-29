-- Aggiornamento conteggio vendite su widget
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS( \" \", REPLACE( REPLACE( REPLACE( FORMAT (SUM(subtotale-sconto) + SUM( (subtotale-sconto)/100*co_iva.percentuale),2), \",\", \"#\"), \".\", \",\" ), \"#\", \".\"), \"&euro;\" ) AS dato FROM vb_venditabanco_righe INNER JOIN vb_venditabanco ON vb_venditabanco_righe.idvendita = vb_venditabanco.id LEFT OUTER JOIN co_iva ON vb_venditabanco_righe.idiva=co_iva.id WHERE (CAST(`data` AS DATE) BETWEEN \'|period_start|\' AND \'|period_end|\')' WHERE `zz_widgets`.`name` = 'Vendite al banco';

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `vb_venditabanco`
    INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
    LEFT OUTER JOIN (
        SELECT `vb_venditabanco_righe`.`idvendita`, SUM(`vb_venditabanco_righe`.`subtotale` - `vb_venditabanco_righe`.`sconto`) AS `totale`, GROUP_CONCAT(`mg_articoli`.`descrizione`) AS `articoli`
        FROM `vb_venditabanco_righe` INNER JOIN `mg_articoli` ON  `vb_venditabanco_righe`.`idarticolo` = `mg_articoli`.`id`
        GROUP BY `idvendita`
    ) AS righe ON `vb_venditabanco`.`id` = `righe`.`idvendita`
    WHERE deleted_at IS NULL AND 1=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Vendita al banco';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'id', '`vb_venditabanco`.`id`', 1, 1, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'Numero', '`vb_venditabanco`.`numero`', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'Totale', '`righe`.`totale`', 3, 1, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'Articoli', '`righe`.`articoli`', 4, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'Data', '`vb_venditabanco`.`data`', 5, 1, 1, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'icon_Stato', '`vb_stati_vendita`.`icona`', 6, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Vendita al banco'), 'icon_title_Stato', '`vb_stati_vendita`.`descrizione`', 6, 1, 0, 1, 0, 0);

-- Introduzione campo deleted_at
ALTER TABLE `vb_venditabanco` ADD `deleted_at` TIMESTAMP NULL;

-- Rimozione campi inutilizzati provvigione
ALTER TABLE `mg_articoli` DROP `provvigione`;




