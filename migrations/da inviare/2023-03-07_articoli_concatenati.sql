INSERT INTO `zz_plugins` (`name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`, `created_at`, `updated_at`) VALUES
('Articoli concatenati',	'Articoli concatenati',	21,	21,	'tab',	'articoli.concatenati.php',	1,	1,	0,	'',	'',	NULL,	NULL,	'',	'',	'2022-03-07 10:22:56',	'2022-03-07 10:22:56');

CREATE TABLE `mg_articoli_concatenati` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_articolo` int NOT NULL,
  `id_articolo_concatenato` int NOT NULL,
  `prezzo` float NOT NULL DEFAULT '0',
  `prezzo_ivato` float NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
);

ALTER TABLE `mg_articoli_concatenati`
ADD `iva` int NOT NULL DEFAULT '0' AFTER `prezzo`;
