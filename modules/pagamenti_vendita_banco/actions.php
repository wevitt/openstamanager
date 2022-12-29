<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add_element':
        $id_element = post('id_element');
        $tipologia = post('tipologia');

        if (!empty($id_element) && !empty($tipologia)) {
            $descrizione = $dbo->fetchOne('SELECT descrizione FROM co_pagamenti WHERE id = '.prepare($id_element))['descrizione'];

            $dbo->update('co_pagamenti', [
                'tipo_xon_xoff' => $tipologia,
            ], ['descrizione' => $descrizione]);
        }

        $id_record = $tipologia;

        break;

    case 'remove_element':
        $id_element = post('id_element');
        $tipologia = post('tipologia');

        if (!empty($id_element) && !empty($tipologia)) {
            $descrizione = $dbo->fetchOne('SELECT descrizione FROM co_pagamenti WHERE id = '.prepare($id_element))['descrizione'];

            $dbo->update('co_pagamenti', [
                'tipo_xon_xoff' => null,
            ], ['descrizione' => $descrizione]);
        }

        break;
}
