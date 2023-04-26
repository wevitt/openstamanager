<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Ordini\Components\Articolo;
use Modules\Ordini\Ordine;
use Modules\Ordini\Tipo;
use Plugins\ListinoClienti\DettaglioPrezzo;

switch (post('op')) {
    case 'crea_ordine':
        $ordinare = post('ordinare');
        $qta_ordinare = post('qta_ordinare');
        $id_fornitori = post('idanagrafica');
        $sedi = post('sede_partenza');

        //modal options
        $inBozza = post('inBozza');
        $magazzinoScelto = post('magazzinoScelto');
        $spostamento_interno = post('spostamentoInterno');

        // Lista degli articoli da ordinare
        $articoli = [];
        foreach ($ordinare as $key => $value) {
            if ($value == 'on') {
                $id_articolo = explode('_', $key)[1];

                $articoli[] = [
                    'id_articolo' => $id_articolo,
                    'id_fornitore' => $id_fornitori[$key],
                    'qta' => $qta_ordinare[$key],
                    'sede' => $sedi[$key],
                ];
            }
        }
        $articoli = collect($articoli);

        if ($spostamento_interno != 0) { //spostamento interno
            $qta = $articoli[0]['qta'];
            $disponibilita = getDisponibilitaMagazzini($articoli[0]['id_articolo']);

            $anagrafica = Anagrafica::find(1);
            $tipo = Tipo::where('dir', 'uscita')->first();

            $magazzini = [];
            foreach ($disponibilita as $magazzino) {
                if ($qta > 0 && $magazzino['id'] != $spostamento_interno) {
                    $ordine = Ordine::where('idanagrafica', 1)
                        ->where('idstatoordine', 1)
                        ->where('idsede', $magazzino['id'])
                        ->where('id_sede_partenza', $spostamento_interno)
                        ->first();

                    if ($inBozza == 0 || empty($ordine)) {
                        $ordine = Ordine::build($anagrafica, $tipo, date('Y-m-d'));
                        $ordine->idSede = $magazzino['id'];
                        $ordine->id_sede_partenza = $spostamento_interno;
                        $ordine->save();
                    }

                    $articolo = ArticoloOriginale::find($articoli[0]['id_articolo']);
                    $riga_articolo = Articolo::build($ordine, $articolo);

                    $qta_da_spostare = 0;

                    //convert $mgazzino['giacenza] to float
                    $magazzino['giacenza'] = floatval($magazzino['giacenza']);

                    if ($magazzino['giacenza'] > $qta) {
                        $qta_da_spostare = $qta;
                    } else {
                        $qta_da_spostare = $magazzino['giacenza'];
                        $qta -= $magazzino['giacenza'];
                    }

                    $riga_articolo->qta = $qta_da_spostare;
                    $riga_articolo->confermato=setting('Conferma automaticamente le quantità negli ordini fornitore');

                    $riga_articolo->save();
                }
            }

            flash()->info(tr('Spostamento interno effettuato!'));
        } else { //ordine a fornitore

            if ($magazzinoScelto != '') { //unica sede
                $fornitori = $articoli->where('id_fornitore', '<>', '')->groupBy('id_fornitore');

                foreach ($fornitori as $id_fornitore => $articoli) {
                    $anagrafica = Anagrafica::find($id_fornitore);
                    $tipo = Tipo::where('dir', 'uscita')->first();

                    $ordine = Ordine::where('idanagrafica', $id_fornitore)
                        ->where('idstatoordine', 1)
                        ->first();

                    if ($inBozza == 0 || empty($ordine)) {
                        $ordine = Ordine::build($anagrafica, $tipo, date('Y-m-d'));
                        $ordine->id_sede_partenza = $magazzinoScelto;
                        $ordine->save();
                    }

                    // Selezione IVA del fornitore
                    $id_iva = $anagrafica->idiva_acquisti ?: setting('Iva predefinita');

                    // Aggiunta degli articolo all'ordine
                    foreach ($articoli as $informazioni) {
                        $articolo = ArticoloOriginale::find($informazioni['id_articolo']);
                        $riga_articolo = Articolo::build($ordine, $articolo);

                        $riga_articolo->id_iva = $id_iva;

                        $fornitore = DettaglioPrezzo::dettagli($articolo->id, $anagrafica->id, 'uscita', $informazioni['qta'])->first();
                        if (empty($fornitore)) {
                            $fornitore = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $anagrafica->id, 'uscita')->first();
                        }

                        $prezzo_unitario = $fornitore->prezzo_unitario - ($fornitore->prezzo_unitario * $fornitore->percentuale / 100);

                        $riga_articolo->setPrezzoUnitario($fornitore ? $prezzo_unitario : $articolo->prezzo_acquisto, $id_iva);
                        $riga_articolo->setSconto($fornitore->sconto_percentuale ?: 0, 'PRC');

                        $riga_articolo->qta = $informazioni['qta'];
                        $riga_articolo->confermato=setting('Conferma automaticamente le quantità negli ordini fornitore');

                        $riga_articolo->save();
                    }
                }
            } else { // multi sede
                $fornitori = $articoli->where('id_fornitore', '<>', '')->groupBy(['id_fornitore', 'sede']);

                foreach ($fornitori as $id_fornitore => $articoli) {
                    foreach ($articoli as $id_sede => $articoli_sede) {
                        $anagrafica = Anagrafica::find($id_fornitore);
                        $tipo = Tipo::where('dir', 'uscita')->first();

                        $ordine = Ordine::where('idanagrafica', $id_fornitore)
                            ->where('idstatoordine', 1)
                            ->first();

                        if ($inBozza == 0 || empty($ordine)) {
                            $ordine = Ordine::build($anagrafica, $tipo, date('Y-m-d'));
                            $ordine->id_sede_partenza = $id_sede;
                            $ordine->save();
                        }

                        // Selezione IVA del fornitore
                        $id_iva = $anagrafica->idiva_acquisti ?: setting('Iva predefinita');

                        // Aggiunta degli articolo all'ordine
                        foreach ($articoli_sede as $informazioni) {
                            $articolo = ArticoloOriginale::find($informazioni['id_articolo']);
                            $riga_articolo = Articolo::build($ordine, $articolo);

                            $riga_articolo->id_iva = $id_iva;

                            $fornitore = DettaglioPrezzo::dettagli($articolo->id, $anagrafica->id, 'uscita', $informazioni['qta'])->first();
                            if (empty($fornitore)) {
                                $fornitore = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $anagrafica->id, 'uscita')->first();
                            }

                            $prezzo_unitario = $fornitore->prezzo_unitario - ($fornitore->prezzo_unitario * $fornitore->percentuale / 100);

                            $riga_articolo->setPrezzoUnitario($fornitore ? $prezzo_unitario : $articolo->prezzo_acquisto, $id_iva);
                            $riga_articolo->setSconto($fornitore->sconto_percentuale ?: 0, 'PRC');

                            $riga_articolo->qta = $informazioni['qta'];
                            $riga_articolo->confermato=setting('Conferma automaticamente le quantità negli ordini fornitore');

                            $riga_articolo->save();
                        }
                    }
                }
            }

            flash()->info(tr('Ordini a fornitore creati!'));
        }


        break;

    case 'get_disponibilita_magazzini':
        $id_articolo = post('id_articolo');

        $ret = getDisponibilitaMagazzini($id_articolo);

        echo json_encode($ret);

        break;
}

/**
 * @param $id_articolo id dell'articolo
 *
 * @return array disponibilita per sede
 */
function getDisponibilitaMagazzini($id_articolo) {
    $dbo = database();

    $articoloSede = $dbo->fetchArray(
        'SELECT ars.id_sede as id,
        IF (id_sede = 0, CONCAT(\'Sede legale - \', ana.citta), s.nomesede) as descrizione
        FROM mg_articoli_sedi ars
        LEFT JOIN an_sedi s ON ars.id_sede = s.id
        LEFT JOIN an_anagrafiche ana ON ana.idanagrafica = 1
        WHERE id_articolo = '.prepare($id_articolo)
    );

    $articolo = ArticoloOriginale::find($id_articolo);
    $giacenze = $articolo->getGiacenze();
    //order by giacenza
    usort($articoloSede, function ($a, $b) use ($giacenze) {
        return $giacenze[$a['id']][0] < $giacenze[$b['id']][0];
    });


    $ret = [];

    foreach ($articoloSede as $sede) {
        $ret[] = [
            'id' => $sede['id'],
            'descrizione' => $sede['descrizione'],
            'giacenza' => numberFormat($giacenze[$sede['id']][0], 2),
            'um' => $articolo->um,
        ];
    }

    return $ret;
}
