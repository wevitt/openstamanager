<?php

include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'reparto':
        $database->update('mg_articoli', [
            'id_reparto' => post('id_reparto'),
        ], ['id' => $id_record]);

        flash()->info(tr('Reparto aggiornato!'));

        break;
}
