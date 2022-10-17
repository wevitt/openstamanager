<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Carbon\Carbon;
use Centralino3CX\Chiamata;
use Models\Module;
use Modules\Emails\Mail;
use Modules\Impianti\Impianto;
use Modules\Scadenzario\Scadenza;

include_once __DIR__.'/../../core.php';

$anagrafica = $chiamata->anagrafica;
$sede = $chiamata->sede;
$referente = $chiamata->referente;
$tecnico = $chiamata->tecnico;

$modulo_anagrafiche = Module::pool('Anagrafiche');
$modulo_impianti = Module::pool('Impianti');

$stati_gestione = [
    [
        'id' => 0,
        'text' => tr('Aperta'),
    ],
    [
        'id' => 1,
        'text' => tr('Gestita'),
    ],
];

echo '
<script src="'.base_url().'/modules/3cx_centralino/functions.js"></script>
<p><b>'.tr('Numero').':</b> '.$chiamata->numero.'</p>';

if (!empty($anagrafica)) {
    echo '
<p>
    '.$anagrafica->ragione_sociale.'<br>
    '.$anagrafica->indirizzo.'<br>
    '.$anagrafica->cap.' '.$anagrafica->citta.' ('.$anagrafica->provincia.')
</p>';
}

echo '
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Informazioni').'</h3>
    </div>

    <div class="panel-body">
        <div class="row">';

// Informazioni sulla chiamata
if (!empty($anagrafica)) {
    echo '
            <div class="col-md-3">
                <h4>'.tr('Anagrafica').'</h4>
                '.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale.($anagrafica->deleted_at ? ' ['.tr('eliminata').']' : '')).'
            </div>';

    if (!empty($sede)) {
        echo '
            <div class="col-md-3">
                <h4>'.tr('Sede').'</h4>
                '.Plugins::link('Sedi', $anagrafica->id, $sede->nomesede).'
            </div>';
    } elseif (!empty($referente)) {
        echo '
            <div class="col-md-3">
                <h4>'.tr('Referente').'</h4>
                '.Plugins::link('Referenti', $anagrafica->id, $referente->nome).'
            </div>';
    } else {
        echo '
            <div class="col-md-3"></div>';
    }

    echo '
            <div class="col-md-3"></div>';
}
// Gestione associazione anagrafica
else {
    $collegamento = $chiamata->trovaNumero();

    echo '
            <div class="col-md-9 text-danger">
                <div class="btn-group pull-right" role="group">
                    <button type="button" class="btn btn-success" onclick="ricercaAssociazione()">
                        <i class="fa fa-search"></i> '.(empty($collegamento) ? tr('Ricerca associazione') : tr('Completa associazione')).'
                    </button>';

    if (empty($collegamento)) {
        echo '
                    <button type="button" class="btn btn-warning" onclick="creaAnagrafica()">
                        <i class="fa fa-plus"></i> '.tr('Crea anagrafica').'
                    </button>

                    <button type="button" class="btn btn-info" onclick="associaNumero()">
                        <i class="fa fa-edit"></i> '.tr('Associa numero').'
                    </button>';
    }

    echo '
                </div>

                <h4>'.tr('Anagrafica').'</h4>
                <p>'.(empty($collegamento) ? tr('Numero di telefono non associato ad una anagrafica!') : tr("Completa l'associazione con l'anagrafica trovata tramite il pulsante \"Completa associazione\"!")).'</p>
            </div>';
}

echo '
            <div class="col-md-3">
                <h4>'.tr('Tecnico').'</h4>
                '.($tecnico ? Modules::link('Anagrafiche', $tecnico->id, $tecnico->ragione_sociale) : tr('Informazione non disponibile')).'
            </div>
        </div>

        <!-- Informazioni sulla chiamata effettiva -->
        <div class="row">
            <div class="col-md-2">
                <h4>'.tr('Inizio').'</h4>
                '.timestampFormat($chiamata->inizio).'
            </div>

            <div class="col-md-2">
                <h4>'.tr('Fine').'</h4>
                '.timestampFormat($chiamata->fine).'
            </div>

            <div class="col-md-2">
                <h4>'.tr('Durata').'</h4>
                '.$chiamata->durata_visibile.' ('.tr('_TOT_ secondi', [
        '_TOT_' => $chiamata->durata,
    ]).')
            </div>

            <div class="col-md-6">
                <h4>'.tr('Oggetto').'</h4>
                '.$chiamata->oggetto.'
            </div>
        </div>
    </div>
</div>

<script>
function ricercaAssociazione() {
    window.location.href = globals.rootdir + "/editor.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=ricerca&backto=record-edit";
}

function creaAnagrafica() {
    openModal("'.tr('Nuova anagrafica').'", globals.rootdir + "/add.php?id_module='.$modulo_anagrafiche->id.'")
        .then(function() {
            // Apertura dettagli anagrafici
            $(".btn-box-tool").click();

            // Impostazione numero di telefono
            input("telefono").set("'.$chiamata->numero.'")
        });
}

function associaNumero() {
    window.open(globals.rootdir + "/controller.php?id_module='.$modulo_anagrafiche->id.'", "_blank");
}
</script>

<div class="row">
    <!-- Informazioni sulla gestione della chiamata -->
    <div class="col-md-6">
        <form action="" method="post" id="edit-form">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="update">

            <h4>'.tr('Stato').'</h4>
            {[ "type": "select", "name": "is_gestito", "values": '.json_encode($stati_gestione).', "value": "'.$chiamata->is_gestito.'" ]}

            <h4>'.tr('Descrizione').'</h4>
            {[ "type": "ckeditor", "placeholder": "'.tr('Descrizione').'", "name": "descrizione", "id": "descrizione", "value": "'.$chiamata->descrizione.'" ]}
        </form>
    </div>

    <div class="col-md-6">
        <h4>'.tr('Ultime chiamate').'</h4>
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th width="180px">'.tr('Data').'</th>
                    <th width="10px">'.tr('Risp.').'</th>
                    <th width="150px">'.tr('Tecnico').'</th>
                    <th>'.tr('Descrizione').'</th>
                </tr>
            </thead>

            <tbody>';

// Elenco chiamate recenti per anagrafica (o numero)
if (!empty($anagrafica)) {
    $chiamate_recenti = Chiamata::where('id_anagrafica', '=', $anagrafica->id);
} else {
    $chiamate_recenti = Chiamata::where('numero', '=', $chiamata->numero);
}

$chiamate_recenti = $chiamate_recenti->latest()
    ->take(10)->get();
$prima_chiamata = $chiamate_recenti->first();
$ultima_chiamata = $chiamate_recenti->last();

if ($prima_chiamata->inizio->lessThan($chiamata->inizio)) {
    echo '
                <tr class="bg-info">
                    <td>
                        <a href="'.base_url().'/editor.php?id_module='.$id_module.'&id_record='.$chiamata->id.'">
                            '.timestampFormat($chiamata->inizio).' ['.$chiamata->durata_visibile.']
                        </a>
                    </td>
                    <td><i class="'.($chiamata->is_risposta ? 'fa fa-phone' : 'fa fa-times-circle').'"></i></td>
                    <td>'.($chiamata->tecnico ? $chiamata->tecnico->ragione_sociale : '-').'</td>
                    <td>'.$chiamata->descrizione.'</td>
                </tr>';
}

$chiamata_corrente_trovata = false;
foreach ($chiamate_recenti as $c) {
    $chiamata_corrente_trovata |= $c->id == $chiamata->id;

    echo '
                <tr class="'.($c->id == $chiamata->id ? 'bg-info' : '').'">
                    <td>
                        <a href="'.base_url().'/editor.php?id_module='.$id_module.'&id_record='.$c->id.'">
                            '.timestampFormat($c->inizio).' ['.$c->durata_visibile.']
                        </a>
                    </td>
                    <td><i class="'.($c->is_risposta ? 'fa fa-phone' : 'fa fa-times-circle').'"></i></td>
                    <td>'.($c->tecnico ? $c->tecnico->ragione_sociale : '-').'</td>
                    <td>'.$c->descrizione.'</td>
                </tr>';
}

if ($ultima_chiamata->inizio->greaterThan($chiamata->inizio)) {
    echo '
                <tr class="bg-info">
                    <td>
                        <a href="'.base_url().'/editor.php?id_module='.$id_module.'&id_record='.$chiamata->id.'">
                            '.timestampFormat($chiamata->inizio).' ['.$chiamata->durata_visibile.']
                        </a>
                    </td>
                    <td><i class="'.($chiamata->is_risposta ? 'fa fa-phone' : 'fa fa-times-circle').'"></i></td>
                    <td>'.($chiamata->tecnico ? $chiamata->tecnico->ragione_sociale : '-').'</td>
                    <td>'.$chiamata->descrizione.'</td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>
</div>';

if (empty($anagrafica)) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Per ottenere maggiori informazioni sul contatto è necessario associare l'anagrafica relativa al numero di telefono indicato").'.
</div>';

    return;
}

// Ticket per l'anagrafica dal modulo Gestione Progetti
$modulo_devboard = Module::pool('DevBoard');
if (!empty($modulo_devboard)) {
    $tickets = $database->fetchArray('SELECT ac_ticket.id FROM ac_ticket
        LEFT JOIN ac_commenti_ticket ON ac_commenti_ticket.id_ticket = ac_ticket.id
        LEFT JOIN co_preventivi ON co_preventivi.id = ac_ticket.id_preventivo
    WHERE co_preventivi.idanagrafica = '.prepare($chiamata->id_anagrafica).'
    GROUP BY ac_ticket.id
    ORDER BY IFNULL(MAX(ac_commenti_ticket.created_at), ac_ticket.created_at) DESC
    LIMIT 0, 10');

    echo '
<div class="row">
    <div class="col-md-12">
        <a href="'.base_url().'/controller.php?id_module='.$modulo_devboard->id.'" class="pull-right">
            '.tr('Modulo DevBoard').' <i class="fa fa-external-link"></i>
        </a>

        <h4>'.tr('Ticket').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th width="200" class="text-center">'.tr('Progetto').'</th>
                    <th width="140" class="text-center">'.tr('Entro').'</th>
                    <th width="140" class="text-center">'.tr('Creazione').'</th>
                    <th width="20" class="text-center">#</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($tickets as $info) {
        $ticket = \Modules\Progetti\Ticket::find($info['id']);

        // Fix per classe Ticket non aggiornata con le date
        $data_prevista = new Carbon($ticket->data_prevista);
        $data_creazione = new Carbon($ticket->created_at);

        // Gestione icone dello stato
        $stato = $ticket->stato;
        $icona = $stato->icona;
        $descrizione_icona = $stato->descrizione;
        if (!empty($ticket['notified2_at']) && $stato->descrizione == 'Chiuso') {
            $icona = 'fa fa-check-circle text-success';
            $descrizione_icona = tr('Chiuso automaticamente il _DATE_', [
                '_DATE_' => dateFormat($ticket['notified2_at']),
            ]);
        }

        echo '
                <tr>
                    <td>
                        '.($ticket['priorita'] ? '<i class="fa fa-flag text-'.($ticket['priorita'] == 1 ? 'warning' : 'danger').'"></i>' : '').'

                        <i class="'.$icona.' tip" title="'.$descrizione_icona.'" ></i> #'.$ticket['id'].' - '.($ticket['completato'] ? '<s>' : '').$ticket['titolo'].($ticket['completato'] ? '</s>' : '').'

                        '.(empty($ticket['info_rapide']) ? '' : '<br><small class="label label-danger">'.trim($ticket['info_rapide'], '<br>').'</small>').'
                    </td>
                    <td>'.reference($ticket->progetto).'</td>
                    <td>'.$data_prevista->diffForHumans().'</td>
                    <td>'.$data_creazione->diffForHumans().'</td>
                    <td>';

        // Individuazione commenti recenti
        $commenti = $ticket->commenti();
        $commenti_non_letti_da_cliente = (clone $commenti)
            ->where('is_gestito_cliente', false)
            ->count();

        if ($commenti_non_letti_da_cliente) {
            $ultimo_commento = (clone $commenti)->latest()->first();
            $utente_commento = $ultimo_commento->user->anagrafica;

            echo '
                        <i class="fa fa-hourglass-start tip pull-right text-muted" style="position:relative; margin-left:-15px; top:5px;" title="'.tr('Il cliente non ha ancora visionato i commenti del _DATE_ inseriti da _USER_ (_TIME_)', [
                    '_USER_' => $utente_commento->ragione_sociale,
                    '_TIME_' => $ultimo_commento->created_at->diffForHumans(),
                    '_DATE_' => dateFormat($ultimo_commento->created_at),
                ]).'"></i>';
        }

        echo '
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>
</div>';
}

echo '
<div class="row">';

// Fatture recenti dell'anagrafica
$modulo_fatture = Module::pool('Fatture di vendita');
if ($modulo_fatture->permission != '-') {
    $fatture = $anagrafica->fatture()
        ->orderBy('data', 'DESC')->take(20)->get();
    echo '
    <div class="col-md-6">
        <h4>'.tr('Fatture recenti').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Riferimento').'</th>
                    <th>'.tr('Note interne').'</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($fatture as $fattura) {
        echo '
                <tr>
                    <td>'.reference($fattura).'</td>
                    <td>'.$fattura->note_aggiuntive.'</td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

// Interventi collegati all'anagrafica
$modulo_interventi = Module::pool('Interventi');
if ($modulo_interventi->permission != '-') {
    $interventi = $anagrafica->interventi()
        ->orderBy('data_richiesta', 'DESC')->take(20)->get();
    echo '
    <div class="col-md-6">
        <h4>'.tr('Attività recenti').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Riferimento').'</th>
                    <th>'.tr('Richiesta').'</th>
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
        </table>
    </div>';
}

// Scadenze dell'anagrafica
$modulo_scadenze = Module::pool('Scadenziario');
if ($modulo_scadenze->permission != '-') {
    $scadenze = $database->fetchArray('SELECT co_scadenziario.id FROM co_scadenziario
    INNER JOIN co_documenti ON co_scadenziario.iddocumento = co_documenti.id
WHERE co_scadenziario.iddocumento != 0 AND co_documenti.idanagrafica = '.prepare($chiamata->id_anagrafica).' AND co_scadenziario.da_pagare != co_scadenziario.pagato
ORDER BY co_scadenziario.scadenza DESC');
    echo '
    <div class="col-md-6">
        <h4>'.tr('Scadenze').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Riferimento').'</th>
                    <th>'.tr('Scadenza').'</th>
                    <th>'.tr('Totale').'</th>
                    <th>'.tr('Da pagare').'</th>
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
        </table>
    </div>';
}

// Impianti dell'anagrafica
$modulo_impianti = Module::pool('Impianti');
if ($modulo_impianti->permission != '-') {
    $impianti = Impianto::where('idanagrafica', '=', $anagrafica->id)
        ->get();
    echo '
    <div class="col-md-6">
        <h4>'.tr('Impianti').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Matricola').'</th>
                    <th>'.tr('Nome').'</th>
                    <th>'.tr('Marca').'</th>
                    <th>'.tr('Modello').'</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($impianti as $impianto) {
        echo '
                <tr>
                    <td>
                        <a href="'.base_url().'/editor.php?id_module='.$modulo_impianti->id.'&id_record='.$impianto->id.'">'.$impianto->matricola.'</a>
                    </td>
                    <td>'.$impianto->name.'</td>
                    <td>'.$impianto->marca.'</td>
                    <td>'.$impianto->modello.'</td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

echo '
</div>';

// Elenco email recenti
// Moduli interessati: DevBoard, Preventivi, Contratti, Scadenzario, Fatture di vendita, Interventi, Anagrafiche
$modulo_email = Module::pool('Stato email');
if ($modulo_email->permission != '-') {
    $id_email_recenti = [];

    // Email delle Fatture
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN co_documenti ON co_documenti.id = em_emails.id_record
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita')
    AND co_documenti.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email dei Preventivi
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN co_preventivi ON co_preventivi.id = em_emails.id_record
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Preventivi')
    AND co_preventivi.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email dei Contratti
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN co_contratti ON co_contratti.id = em_emails.id_record
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Contratti')
    AND co_contratti.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email degli Interventi
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN in_interventi ON in_interventi.id = em_emails.id_record
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Interventi')
    AND in_interventi.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email deale Anagrafiche
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = em_emails.id_record
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Anagrafiche')
    AND an_anagrafiche.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email dallo Scadenzario
    $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN co_scadenziario ON co_scadenziario.id = em_emails.id_record
    INNER JOIN co_documenti ON co_scadenziario.iddocumento = co_documenti.id
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'Scadenzario')
    AND co_documenti.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');

    // Email dalla DevBoard
    if (!empty($modulo_devboard)) {
        $id_email_recenti[] = $database->fetchArray("SELECT em_emails.id FROM em_emails
    INNER JOIN em_templates ON em_templates.id = em_emails.id_template
    INNER JOIN ac_ticket ON ac_ticket.id = em_emails.id_record
    INNER JOIN co_preventivi ON ac_ticket.id_preventivo = co_preventivi.id
WHERE em_templates.id_module = (SELECT id FROM zz_modules WHERE name = 'DevBoard')
    AND co_preventivi.idanagrafica = ".prepare($chiamata->id_anagrafica).'
ORDER BY em_emails.created_at
LIMIT 0,10');
    }

    $id_email = collect($id_email_recenti)->flatten();

    $elenco_email = Mail::whereIn('id', $id_email)
        ->latest()->take(20)->get();
    echo '
<div class="row">
    <div class="col-md-12">
        <h4>'.tr('Email recenti').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Mittente').'</th>
                    <th>'.tr('Destinatario').'</th>
                    <th>'.tr('Template').'</th>
                    <th>'.tr('Data creazione').'</th>
                    <th>'.tr('Data invio').'</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($elenco_email as $email) {
        echo '
                <tr>
                    <td>'.$email->account->from_name.' <'.$email->account->from_address.'></td>
                    <td>'.implode(', ', $email->receivers->pluck('address')->all()).'</td>
                    <td>'.$email->template->name.'</td>
                    <td>
                        <a href="'.base_url().'/editor.php?id_module='.$modulo_email->id.'&id_record='.$email->id.'">
                            '.timestampFormat($email->created_at).'
                        </a>
                    </td>
                    <td>'.timestampFormat($email->sent_at).'</td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>
</div>';
}
