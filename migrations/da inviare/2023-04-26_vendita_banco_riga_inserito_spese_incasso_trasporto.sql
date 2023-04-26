ALTER TABLE `vb_righe_venditabanco`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `is_spesa_incasso` tinyint(1) NULL DEFAULT '0',
ADD `is_spesa_trasporto` tinyint(1) NULL DEFAULT '0' AFTER `is_spesa_incasso`;
