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

use Models\Module;
use Modules\TipiIntervento\Tipo as TipoSessione;

include_once __DIR__.'/../../core.php';

// Visualizzazione pulsanti solo se anagrafica associata
if (empty($chiamata->anagrafica)) {
    return;
}

$intervento = $chiamata->intervento;
$modulo_interventi = Module::pool('Interventi');

// Informazioni sui movimenti interni
if (!empty($intervento)) {
    echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Questa chiamata è stata associata ad una Attivitò').'.">
    <a class="btn btn-info" href="'.base_url().'/editor.php?id_module='.$modulo_interventi->id.'&id_record='.$intervento->id.'">
        <i class="fa fa-link"></i> '.tr('Attività associata').'
    </a>
</div>';

    return;
}

$id_anagrafica = $chiamata->id_anagrafica;

$codice_tipo = 'CALL';
$tipo = TipoSessione::where('codice', '=', $codice_tipo)->first();

$inizio = $chiamata->inizio;
$fine = $chiamata->fine;

echo '
<button type="button" class="btn btn-success" onclick="ricercaAssociazione()">
    <i class="fa fa-search"></i> '.tr('Aggiorna anagrafica associata').'
</button>

<div class="tip" data-toggle="tooltip" title="'.tr('Crea una attività a partire da questa chiamata').'.">
    <button class="btn btn-warning" onclick="creaIntervento()">
        <i class="fa fa-plus"></i> '.tr('Crea attività').'
    </button>
</div>

<div class="tip" data-toggle="tooltip" title="'.tr('Associa una attività alla chiamata corrente').'.">
    <button class="btn btn-info" onclick="associaIntervento()">
        <i class="fa fa-link"></i> '.tr('Associa attività').'
    </button>
</div>

<script>
function creaIntervento() {
    let params = new URLSearchParams({
        ref: "centralino",
        id_module: "'.$modulo_interventi->id.'",
        idanagrafica: "'.$id_anagrafica.'",
        id_tecnico: "'.$chiamata->tecnico->id.'",
        id_tipo: "'.$tipo->id.'",
        data: "'.$inizio->toDateString().'",
        data_fine: "'.($fine ? $fine->toDateString() : null).'",
        orario_inizio: "'.$inizio->toTimeString().'",
        orario_fine: "'.($fine ? $fine->toTimeString() : null).'",
    }).toString();

    openModal("'.tr('Nuova attività').'", globals.rootdir + "/add.php?" + params)
        .then(function() {
            const body = $(this).find(".modal-body");
            const button = body.find(".btn.btn-primary");

            button.attr("onclick", "salvaAssocia(this)");
        });
}

function associaIntervento() {
    openModal("'.tr('Associa attività').'", "'.$structure->fileurl('associa_intervento.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}

async function salvaAssocia(button) {
    // Submit dinamico tramite AJAX
    let response = await salvaForm("#add-form", {
        id_module: "'.$modulo_interventi->id.'", // Fix creazione da Dashboard
    }, button);

    // Associazione dinamica all\'ultimo intervento generato
    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "POST",
        dataType: "JSON",
        data: {
            op: "associa_ultimo_intervento",
            id_module: globals.id_module,
            id_record: globals.id_record,
        },
        success: function (data) {
            if (data.id_intervento){
                window.location.href = globals.rootdir + "/editor.php?id_module='.$modulo_interventi->id.'&id_record=" + data.id_intervento;
            }
        },
        error: function (gestione) {
        }
    });
}
</script>';
