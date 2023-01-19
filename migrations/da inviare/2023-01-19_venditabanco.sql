ALTER TABLE `vb_venditabanco`
ADD `numero_esterno` varchar(100) NOT NULL AFTER `numero`,
ADD `id_segment` int(11) NOT NULL AFTER `idmagazzino`,
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
