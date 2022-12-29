<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':

        $nome = post('nome');
        $sezionale = post('sezionale');

        //Controllo se esiste già una sorgente con il nome selezionato
        $rs = $dbo->fetchOne("SELECT id FROM bu_sorgenti WHERE LOWER(nome)=LOWER(".prepare($nome).") AND sezionale=".prepare($sezionale));

        if (empty($rs)) {
            $dbo->query("INSERT INTO bu_sorgenti(`nome`,`sezionale`) VALUES (".prepare($nome).",".prepare($sezionale).")");
            $id_record = $dbo->lastInsertedID();
            flash()->info(tr('Nuova sorgente dati inserita!'));
        } else {
            flash()->warning(tr('Attenzione: la sorgente dati _NAME_ risulta già esistente per la sezione _SEZIONE_.', [
                '_NAME_' => $nome,
                '_SEZIONE_' => $sezionale
            ]));
        }

        break;

    case 'update':

        $nome = post('nome');
        $enabled = post('enabled');
        $query = $_POST['query'];
        $query2 = $_POST['query2'];
        $sezionale = post('sezionale');

        //Controllo se esiste già una sorgente con il nome selezionato
        $rs = $dbo->fetchOne("SELECT id FROM bu_sorgenti WHERE LOWER(nome)=LOWER(".prepare($nome).") AND sezionale=".prepare($sezionale)." AND NOT id=".prepare($id_record));
        if (empty($rs)) {
            $dbo->update('bu_sorgenti', [
                'nome' => $nome,
                'query' => $query,
                'query2' => $query2,
                'sezionale' => $sezionale,
                'enabled' => $enabled,
            ], ["id" => $id_record]);
            flash()->info(tr('Sorgente dati aggiornata!'));
        } else {
            flash()->warning(tr('Attenzione: la sorgente dati _NAME_ risulta già esistente per la sezione _SEZIONE_.', [
                '_NAME_' => $nome,
                '_SEZIONE_' => $sezionale
            ]));
        }

        break;

    case 'delete':
        $dbo->query("DELETE FROM bu_sorgenti WHERE id=".prepare($id_record));
        flash()->info(tr('Sorgente dati eliminata!'));
        break;
}
