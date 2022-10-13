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

// Informazioni aggiuntive per Fatture
if ($module['name'] != 'Fatture di acquisto' && $module['name'] != 'Fatture di vendita') {
    return;
}

// Percentuale rivalsa e Percentuale ritenuta d'acconto
if ($options['action'] == 'edit') {
    $id_rivalsa_inps = $result['idrivalsainps'];
    $id_ritenuta_acconto = $result['idritenutaacconto'];
    $calcolo_ritenuta_acconto = $result['calcolo_ritenuta_acconto'];
} elseif ($options['action'] == 'add') {
    // Fattura di acquisto
    if ($options['dir'] == 'uscita') {
        // TODO: Luca S. questi campi non dovrebbero essere definiti all'interno della scheda fornitore?
        $id_rivalsa_inps = '';
        $id_ritenuta_acconto = '';
    }
    // Fattura di vendita
    elseif ($options['dir'] == 'entrata') {
        // Caso particolare per aggiunta articolo
        $id_rivalsa_inps = ($options['op'] == 'addarticolo') ? '' : setting('Cassa previdenziale predefinita');

        $id_ritenuta_acconto = $options['id_ritenuta_acconto_predefined'] ?: setting("Ritenuta d'acconto predefinita");
    }
}

$calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto ?: setting("Metodologia calcolo ritenuta d'acconto predefinito");

echo '
<div class="row">';

    // Cassa previdenziale
    echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr('Cassa previdenziale').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.(($options['dir'] == 'entrata') ? setting('Tipo Cassa Previdenziale') : null).'" ]}
    </div>';

    // Ritenuta d'acconto
    echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
    </div>';

    // Calcola ritenuta d'acconto su
    echo '
    <div class="col-md-4">
        {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\""]}
    </div>';

    echo '
</div>';

if (!empty($options['show-ritenuta-contributi']) || empty($options['hide_conto'])) {
    $width = !empty($options['show-ritenuta-contributi']) && empty($options['hide_conto']) ? 6 : 12;

    echo '
<div class="row">';

    // Ritenuta previdenziale
    if (!empty($options['show-ritenuta-contributi'])) {
        echo '
    <div class="col-md-'.$width.'">
        {[ "type": "checkbox", "label": "'.tr('Ritenuta previdenziale').'", "name": "ritenuta_contributi", "value": "'.$result['ritenuta_contributi'].'" ]}
    </div>';
    }

    // Conto
    if (empty($options['hide_conto'])) {
        echo '
    <div class="col-md-'.$width.'">
        {[ "type": "select", "label": "'.tr('Conto').'", "name": "idconto", "required": 1, "value": "'.$result['idconto'].'", "ajax-source": "'.$options['conti'].'" ]}
    </div>';
    }

    echo '
</div>';
}

echo '
<script>
    $(document).ready(function(){
        if(input("id_ritenuta_acconto").get()){
            $("#calcolo_ritenuta_acconto").prop("required", true);
        } else{
            $("#calcolo_ritenuta_acconto").prop("required", false);
            input("calcolo_ritenuta_acconto").set("");
        }

        $("#id_ritenuta_acconto").on("change", function(){
            if(input("id_ritenuta_acconto").get()){
                $("#calcolo_ritenuta_acconto").prop("required", true);
                
            } else{
                $("#calcolo_ritenuta_acconto").prop("required", false);
                input("calcolo_ritenuta_acconto").set("");
            }
        });
    });
</script>';
