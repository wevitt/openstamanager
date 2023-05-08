ALTER TABLE `co_pagamenti`
CHANGE `updated_at` `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD `importo_spese_di_incasso` float NOT NULL DEFAULT '0';
