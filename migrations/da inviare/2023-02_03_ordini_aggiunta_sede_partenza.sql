ALTER TABLE `or_ordini`
ADD `id_sede_partenza` int(11) NOT NULL AFTER `idreferente`,
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
