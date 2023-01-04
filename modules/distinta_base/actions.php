<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

switch (filter('op')) {
    case 'update':
        $articolo = Articolo::find($id_record);

        $articolo->sincronizza_prezzo_vendita = post('sincronizza_prezzo_vendita');
        $articolo->sincronizza_prezzo_acquisto = post('sincronizza_prezzo_acquisto');
        $articolo->save();

        flash()->info(tr('Articolo aggiornato!'));

        break;

    case 'manage_figlio':
        $articolo = Articolo::find(post('id_articolo'));
        $figlio = Articolo::find(post('id_figlio'));

        $articolo->componenti()->syncWithoutDetaching([
            $figlio->id => [
                'qta' => post('qta'),
            ],
        ]);

        $articolo->triggerChange($figlio);

        flash()->info(tr('Distinta base aggiornata!'));

        break;

    case 'delete_figlio':
        $articolo = Articolo::find(post('id_articolo'));
        $figlio = Articolo::find(post('id_figlio'));

        $articolo->componenti()->detach([
            $figlio->id,
        ]);

        $articolo->triggerChange($figlio);

        flash()->info(tr('Distinta base aggiornata!'));

        break;

    case 'update_qta':
        $figlio = Articolo::find($id_record);

        foreach (post('qta') as $id_articolo => $qta) {
            $articolo = Articolo::find($id_articolo);

            $articolo->componenti()->syncWithoutDetaching([
                $figlio->id => [
                    'qta' => $qta,
                ],
            ]);

            $articolo->triggerChange($figlio);
        }

        flash()->info(tr('Quantità aggiornate!'));

        break;

    case 'produci':
        $qta = post('qta_produzione');
        $articolo = Articolo::find($id_record);

        $date = date('Y-m-d');
        $articolo->movimenta($qta, tr('Produzione articolo'), $date);
        $articolo->movimentaRicorsivo(-$qta, tr('Produzione articolo'), $date);

        flash()->info(tr('Articolo prodotto!'));

        break;

    case 'scomponi':
        $qta = post('qta_produzione');
        $articolo = Articolo::find($id_record);

        $date = date('Y-m-d');
        $articolo->movimenta(-$qta, tr('Scomposizione articolo'), $date);
        $articolo->movimentaRicorsivo($qta, tr('Scomposizione articolo'), $date);

        flash()->info(tr('Articolo scomposto!'));

        break;
}
