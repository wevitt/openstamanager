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
        $inBozza = post('inBozza');

        error_log('ordinare: ' . json_encode($ordinare));
        error_log('qta_ordinare: ' . json_encode($qta_ordinare));
        error_log('idanagrafica: ' . json_encode($id_fornitori));

        // Lista degli articoli da ordinare
        $articoli = [];
        foreach ($ordinare as $id_articolo => $value) {
            if ($value == 'on') {
                $articoli[] = [
                    'id_articolo' => $id_articolo,
                    'id_fornitore' => $id_fornitori[$id_articolo],
                    'qta' => $qta_ordinare[$id_articolo],
                ];
            }
        }

        $articoli = collect($articoli);
        $fornitori = $articoli->where('id_fornitore', '<>', '')->groupBy('id_fornitore');

        foreach ($fornitori as $id_fornitore => $articoli) {
            $anagrafica = Anagrafica::find($id_fornitore);
            $tipo = Tipo::where('dir', 'uscita')->first();

            $ordine = Ordine::where('idanagrafica', $id_fornitore)
                ->where('idstatoordine', 1)
                ->first();

            error_log("ordine: " . $ordine);

            if ($inBozza == 0 || empty($ordine)) {
                $ordine = Ordine::build($anagrafica, $tipo, date('Y-m-d'));
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

        flash()->info(tr('Ordini fornitore creati!'));

        break;

    case 'get_disponibilita_magazzini':
        $id_articolo = post('id_articolo');

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


        $ret = [];

        foreach ($articoloSede as $sede) {
            $ret[] = [
                'id' => $sede['id'],
                'descrizione' => $sede['descrizione'],
                'giacenza' => numberFormat($giacenze[$sede['id']][0], 2),
                'um' => $articolo->um,
            ];
        }

        echo json_encode($ret);

        break;
}
