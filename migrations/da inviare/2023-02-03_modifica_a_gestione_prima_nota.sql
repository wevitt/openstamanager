UPDATE `zz_modules` SET
`id` = '16',
`name` = 'Prima nota',
`title` = 'Prima nota',
`directory` = 'primanota',
`options` = 'SELECT\r\n    |select| \r\nFROM\r\n    `co_movimenti`\r\nINNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id`\r\nLEFT JOIN vb_venditabanco ON vb_venditabanco.id = \r\n co_movimenti.iddocumento\r\nLEFT JOIN `co_documenti` ON `co_documenti`.`id` = `co_movimenti`.`iddocumento`\r\nLEFT JOIN `an_anagrafiche` ON `co_movimenti`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica`\r\nWHERE\r\n    1=1 AND `primanota` = 1  |date_period(`co_movimenti`.`data`)|\r\nGROUP BY\r\n    `idmastrino`,\r\n    `primanota`,\r\n    `co_movimenti`.`data`,\r\nvb_venditabanco.numero_esterno,\r\nco_documenti.numero_esterno,\r\n    `co_movimenti`.`descrizione`,\r\n    `an_anagrafiche`.`ragione_sociale`\r\nHAVING\r\n    2=2\r\nORDER BY\r\n    `co_movimenti`.`data`\r\nDESC',
`options2` = '',
`icon` = 'fa fa-angle-right',
`version` = '2.4.39',
`compatibility` = '2.4.39',
`order` = '5',
`parent` = '12',
`default` = '1',
`enabled` = '1',
`created_at` = '2022-12-21 14:50:58',
`updated_at` = now(),
`use_notes` = '0',
`use_checklists` = '0'
WHERE `id` = '16';
