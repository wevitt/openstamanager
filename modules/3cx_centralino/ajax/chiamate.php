<?php

use Centralino3CX\API\CallNotification;

include_once __DIR__.'/../../../core.php';

$keys = CallNotification::getKeys();

// Restituzione chiave pubblica per la sincronizzazione
if (!empty($_GET['public_key'])) {
    echo $keys['public'];

    return;
}

// Registrazione dell'endpoint esterno
if (!empty($_GET['register'])) {
    $params = file_get_contents('php://input');
    $exists = $database->fetchOne('SELECT id FROM 3cx_push WHERE params = :params', [
        'params' => $params,
    ]);

    if (empty($exists)) {
        $database->insert('3cx_push', [
            'params' => $params,
            'id_utente' => $user['id'],
        ]);
    } else {
        $database->update('3cx_push', [
            'id_utente' => $user['id'],
        ], ['id' => $exists['id']]);
    }

    return;
}
