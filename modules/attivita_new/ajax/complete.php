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
        // Scadenze
        $modulo_scadenze = Module::pool('Contratti');
        if ($modulo_scadenze->permission != '-') {
            // Contratti attivi per l'anagrafica
            $contratti = Contratto::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();


            echo '
            <h4>Scadenze</h4>
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
                ->latest()->take($numero_documenti);

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
        break;
}
