<?php

include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'reparto':
        $database->update('mg_categorie', [
            'id_reparto' => post('id_reparto'),
        ], ['id' => $id_record]);

        flash()->info(tr('Reparto aggiornato!'));

        break;

    case 'sottocategorie':
        $database->query('UPDATE mg_categorie SET id_reparto = '.prepare($categoria['id_reparto']).' WHERE parent = '.prepare($categoria['id']));

        $database->query('UPDATE mg_articoli SET id_reparto = '.prepare($categoria['id_reparto']).' WHERE (id_categoria = '.prepare($categoria['id']).') OR (id_sottocategoria = '.prepare($categoria['id']).')');

        flash()->info(tr('Reparto aggiornato in sottocategorie e articoli!'));

        break;

    case 'articoli':
        $database->query('UPDATE mg_articoli SET id_reparto = '.prepare($categoria['id_reparto']).' WHERE (id_categoria = '.prepare($categoria['id']).' AND id_sottocategoria IS NULL) OR (id_sottocategoria = '.prepare($categoria['id']).')');

        flash()->info(tr('Reparto aggiornato negli articoli!'));

        break;
}
