ALTER TABLE `zz_users`
ADD `id_sede_predefinita` int(11) NULL AFTER `idgruppo`,
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

ALTER TABLE `zz_segments`
ADD `id_sede_predefinita` int(11) NULL AFTER `id_module`,
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
