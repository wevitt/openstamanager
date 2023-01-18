<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;
use Modules\DDT\DDT;
use Modules\Interventi\Intervento;
use Modules\Ordini\Ordine;
use Modules\Preventivi\Preventivo;
use Modules\VenditaBanco\Components\Riga;
use Modules\VenditaBanco\Stato;
use Modules\VenditaBanco\Vendita;

switch (filter('op')) {
    case 'crea_vendita':
        $accodare = post('accodare');
        $module = Modules::get('Vendita al banco');
        $movimenta = 0;

        $module_orig = Modules::get($id_module);

        if ($module_orig->name == 'Interventi') {
            $name = 'intervento';
            $documento = Intervento::find($id_record);
            $rif_riga = 'Modules\\\Interventi\\\Components';
        } elseif ($module_orig->name == 'Preventivi') {
            $name = 'preventivo';
            $campo = 'idpreventivo';
            $documento = Preventivo::with('stato')->find($id_record);
            $idpagamento = $documento->idpagamento;
            $rif_riga = 'Modules\\\Preventivi\\\Components';
            $movimenta = 1;
        } elseif ($module_orig->name == 'Ddt di vendita') {
            $name = 'ddt';
            $documento = DDT::find($id_record);
            $rif_riga = 'Modules\\\DDT\\\Components';
        } elseif ($module_orig->name == 'Ordini cliente') {
            $name = 'ordine';
            $documento = Ordine::find($id_record);
            $rif_riga = 'Modules\\\Ordini\\\Components';
            $movimenta = 1;
        }

        $idpagamento = ($documento->idpagamento ? $documento->idpagamento : setting('Pagamento predefinito'));

        //Se ho deciso di accodare ad una vendita aperta controllo se ce ne sono
        if (!empty($accodare)) {
            $id_vendita = $dbo->fetchOne('SELECT vb_venditabanco.id FROM vb_venditabanco INNER JOIN vb_stati_vendita ON vb_stati_vendita.id=vb_venditabanco.idstato WHERE vb_venditabanco.idanagrafica='.prepare($documento->idanagrafica)." AND vb_stati_vendita.descrizione='Aperto' AND deleted_at IS NULL ORDER BY `data` DESC")['id'];

            $vendita = Vendita::find($id_vendita);
        }

        //Creo la vendita
        if (empty($vendita)) {
            $vendita = Vendita::build();
        }

        $vendita->data = date('y-m-d H:i:s');

        $stato_aperto = Stato::where('descrizione', 'Aperto')->first();
        $vendita->stato()->associate($stato_aperto);

        $vendita->idanagrafica = $documento->idanagrafica;
        $vendita->idpagamento = $idpagamento;
        $vendita->idmagazzino = post('idmagazzino');

        $vendita->save();

        if ($module_orig->name == 'Interventi') {
            aggiungi_intervento_in_vendita($documento->id, $vendita->id, post('ore'), post('diritto'), post('km'));
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                // Individuazione classe di destinazione
                $class = get_class($vendita);
                $namespace = implode('\\', explode('\\', $class, -1));

                $current = get_class($riga);
                $pieces = explode('\\', $current);
                $type = end($pieces);

                $object = $namespace.'\\Components\\'.$type;

                // Attributi dell'oggetto da copiare
                $attributes = $riga->getAttributes();
                unset($attributes['id']);
                unset($attributes['order']);

                $model = new $object();

                // Rimozione attributo in conflitto
                unset($attributes[$model->getDocumentID()]);
                unset($attributes['original_type']);
                unset($attributes['original_document_type']);
                unset($attributes['original_id']);
                unset($attributes['original_document_id']);
                unset($attributes['idsede_partenza']);
                unset($attributes['qta']);
                unset($attributes['idimpianto']);
                unset($attributes['data_evasione']);
                unset($attributes['old_id']);
                unset($attributes['idpreventivo']);
                unset($attributes['idordine']);
                unset($attributes['idddt']);
                unset($attributes['idintervento']);
                unset($attributes['idagente']);
                unset($attributes['confermato']);
                unset($attributes['iva_indetraibile']);
                unset($attributes['id_dettaglio_fornitore']);
                unset($attributes['ora_evasione']);
                unset($attributes['provvigione']);
                unset($attributes['provvigione_unitaria']);
                unset($attributes['provvigione_percentuale']);
                unset($attributes['tipo_provvigione']);
                unset($attributes['note']);

                //Modifico i dati della riga aggiungendo i riferimenti
                if (!empty($riga->idarticolo)) {
                    $articolo = Articolo::find($riga->idarticolo);
                    $id_reparto = $articolo->id_reparto;
                    $attributes['original_type'] = $rif_riga.'\\\Articolo';
                } else {
                    $attributes['original_type'] = $rif_riga.'\\\Riga';
                }
                $attributes['original_id'] = $riga->id;
                $attributes['qta'] = post('qta_da_evadere')[$riga->id];
                $attributes['id_reparto'] = $id_reparto ?: setting('Reparto predefinito');
                if(empty($riga->getAttributes()['subtotale'])){
                    $attributes['subtotale'] = $riga->imponibile;
                }

                $campi = [];
                $valori = [];
                foreach ($attributes as $key => $value) {
                    $campi[] = $key;
                    $valori[] = $value;
                }

                $dbo->query('INSERT INTO vb_righe_venditabanco(idvendita ,'.implode(',', $campi).') VALUES ('.prepare($vendita->id).",'".implode("','", $valori)."')");

                if (!empty($attributes['idarticolo']) && !empty($movimenta)) {
                    add_movimento_magazzino_venditabanco($attributes['idarticolo'], -$attributes['qta'], '', $vendita->id, $vendita->idmagazzino);
                }

                $riga->qta_evasa = $riga->qta_evasa + $attributes['qta'];
                $riga->save();
            }
        }

        $dbo->query('COMMIT');
        header('Location: '.$rootdir.'/editor.php?id_module='.$module['id'].'&id_record='.$vendita->id);
        exit;
        flash()->info(tr('Vendita al banco creata!'));

        break;
}
