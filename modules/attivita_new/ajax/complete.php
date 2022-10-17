<?php

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Preventivi\Preventivo;

include_once __DIR__.'/../../core.php';

$id_anagrafica = get('id_anagrafica');
$op = get('op');
$numero_documenti = 5;

switch ($op) {
    case 'dettagli':
        // Scadenze dell'anagrafica
        $modulo_scadenze = Module::pool('Scadenziario');
        if ($modulo_scadenze->permission != '-') {
            $scadenze = $database->fetchArray('SELECT co_scadenziario.id FROM co_scadenziario
            INNER JOIN co_documenti ON co_scadenziario.iddocumento = co_documenti.id
            WHERE co_scadenziario.iddocumento != 0 AND co_documenti.idanagrafica = '.prepare($chiamata->id_anagrafica).' AND co_scadenziario.da_pagare != co_scadenziario.pagato
            ORDER BY co_scadenziario.scadenza DESC');

            echo '
            <h4>'.tr('Scadenze').'</h4>
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color:#eeeeee">
                        <th scope="col">'.tr('Riferimento').'</th>
                        <th scope="col">'.tr('Scadenza').'</th>
                        <th scope="col">'.tr('Totale').'</th>
                        <th scope="col">'.tr('Da pagare').'</th>
                    </tr>
                </thead>

                <tbody>';
                    foreach ($scadenze as $info) {
                        $scadenza = Scadenza::find($info['id']);
                        $scaduta = $scadenza->scadenza->lessThan(new Carbon());

                        echo '
                        <tr class="'.($scaduta ? 'bg-red' : '').'">
                            <td>'.reference($scadenza->documento).'</td>
                            <td>'.dateFormat($scadenza->scadenza).'</td>
                            <td class="text-right">'.moneyFormat($scadenza->da_pagare).'</td>
                            <td class="text-right">'.moneyFormat($scadenza->da_pagare - $scadenza->pagato).'</td>
                        </tr>';
                    }
                echo '
                </tbody>
            </table>';
        }

        // Informazioni sui contratti
        $modulo_contratti = Module::pool('Contratti');
        if ($modulo_contratti->permission != '-') {
            // Contratti attivi per l'anagrafica
            $contratti = Contratto::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();


            echo '
            <h4>Contratti</h4>
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color:#eeeeee">
                        <th scope="col">#</th>
                        <th scope="col">Descrizione</th>
                        <!--<th scope="col">Data accettazione</th>-->
                        <th scope="col">Data conclusione</th>
                    </tr>
                </thead>
                <tbody>';
                    if (!$contratti->isEmpty()) {
                        foreach ($contratti as $contratto) {
                            echo '
                            <tr>
                                <th scope="row">' . $contratto->getReference() . '</th>
                                <td>' . $contratto->stato->descrizione . '</td>
                                <!--<td>' . dateFormat($contratto->data_accettazione) . '</td>-->
                                <td>' . dateFormat($contratto->data_conclusione) . '</td>
                            </tr>';
                        }
                    } else {
                        echo '
                        <tr>
                            <td colspan="2">'.tr('Nessun contratto attivo per questo cliente').'</td>
                        </tr>';
                    }

                echo '
                </tbody>
            </table>';
        }

        // Informazioni sui preventivi
        $modulo_preventivi = Module::pool('Preventivi');
        if ($modulo_preventivi->permission != '-') {
            // Preventivi attivi
            $preventivi = Preventivo::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();

            echo '
            <h4>Preventivi</h4>
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color:#eeeeee">
                        <th scope="col">#</th>
                        <th scope="col">Descrizione</th>
                    </tr>
                </thead>
                <tbody>';
                    if (!$preventivi->isEmpty()) {
                        foreach ($preventivi as $preventivo) {
                            echo '
                            <tr>
                                <th scope="row">' . $preventivo->getReference() . '</th>
                                <td>' . $preventivo->stato->descrizione . '</td>
                            </tr>';
                        }
                    } else {
                        echo '
                        <tr>
                            <td colspan="2">'.tr('Nessun preventivo attivo per questo cliente').'</td>
                        </tr>';
                    }
                echo '
                </tbody>
            </table>';
        }

        // Note dell'anagrafica
        $anagrafica = Anagrafica::find($id_anagrafica);
        $note_anagrafica = $anagrafica->note;

        echo '
        <h4>Note interne sul cliente</h4>
        <table class="table table-bordered">
            <tbody>';
            if (!empty($note_anagrafica)) {
                echo '
                <tr>
                    <th scope="row">' . $note_anagrafica . '</th>
                </tr>';
            } else {
                echo '
                <tr>
                    <th scope="row">' . tr('Nessuna nota interna per questo cliente') . '</th>
                </tr>';
            }
            echo '
            </tbody>
        </table>';

        // Interventi collegati all'anagrafica
        $modulo_interventi = Module::pool('Interventi');
        if ($modulo_interventi->permission != '-') {
            $interventi = $anagrafica->interventi()->orderBy('data_richiesta', 'DESC')->take(20)->get();
            echo '
            <h4>'.tr('Attività recenti').'</h4>
            <table class="table table-bordered">
                <thead>
                    <tr style="background-color:#eeeeee">
                        <th scope="col">'.tr('Riferimento').'</th>
                        <th scope="col">'.tr('Richiesta').'</th>
                    </tr>
                </thead>

                <tbody>';

                foreach ($interventi as $intervento) {
                    echo '
                    <tr>
                        <td>'.reference($intervento).'</td>
                        <td>'.$intervento->richiesta.'</td>
                    </tr>';
                }

                echo '
                </tbody>
            </table>';
        }

        break;
}
