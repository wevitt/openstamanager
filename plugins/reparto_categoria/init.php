<?php

include_once __DIR__.'/../../core.php';

$categoria = $dbo->fetchOne('SELECT * FROM `mg_categorie` WHERE id='.prepare($id_record));
