UPDATE `zz_plugins` SET `script` = '', `options` = ' { \"main_query\": [ { \"type\": \"table\", \"fields\": \"Numero, Nome, Totale, Stato, Predefinito\", \"query\": \"SELECT co_contratti.id, numero AS Numero, `co_contratti`.`nome` AS Nome, an_anagrafiche.ragione_sociale AS Cliente, FORMAT(righe.totale_imponibile,2) AS Totale, co_staticontratti.descrizione AS Stato, IF(`co_contratti`.`predefined`=1, \'SÌ\', \'NO\') AS Predefinito FROM `co_contratti` LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id` LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto` ) AS righe ON `co_contratti`.`id` =`righe`.`idcontratto` WHERE 1=1 AND `co_contratti`.`idanagrafica`=|id_parent| GROUP BY `co_contratti`.`id` HAVING 2=2 ORDER BY `co_contratti`.`id` ASC\"} ]}', `directory` = 'contratti_anagrafiche' WHERE `zz_plugins`.`name` = 'Contratti del cliente';

ALTER TABLE `co_contratti` ADD `predefined` BOOLEAN NOT NULL AFTER `condizioni_fornitura`;

UPDATE `zz_settings` SET `tipo` = 'list[mese,settimana,giorno,agenda]' WHERE `zz_settings`.`name` = 'Vista dashboard'; 