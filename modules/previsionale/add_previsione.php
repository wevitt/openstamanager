<?php

include_once __DIR__.'/../../core.php';

echo '
<form actions="" id="previsioni_form" method="post">
    <input type="hidden" name="op" value="add_previsione">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-10">
                    {["type":"text", "label":"'.tr('Descrizione').'", "name":"descrizione", "value":"", "required":1 ]}
                </div>
                <div class="col-md-2">
                    {["type":"number", "label":"'.tr('Importo').'", "name":"importo", "value":"", "required":1 ]}
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "select", "label":"'.tr('Conto').'", "name": "id_conto", "value": "", "ajax-source": "conti_budget", "required":1 ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "select", "label":"'.tr('Anagrafica').'", "name": "id_anagrafica", "value": "", "ajax-source": "anagrafiche"]}
                </div>
                <div class="col-md-4">
                    {[ "type": "select", "label":"'.tr('Sezione').'", "name": "sezione", "values": "list=\"economico_finanziario\":\"Economico + Finanziario\", \"economico\":\"Solo economico\", \"finanziario\":\"Solo finanziario\"", "value": "economico_finanziario", "required":1 ]}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <label>'.tr('Ricorrenza').'</label>
        <div>
    </div>
    <div class="row">';
        $mesi = get_mesi_ricorrenze();
        foreach ($mesi as $mese) {
            //Blocco i mesi già trascorsi
            echo '
            <div class="col-md-2">
                <input type="checkbox" '.$disabled.' value="'.($mese['anno'].'-'.$mese['mese']).'" name="ricorrenza[]">'.$mese['descrizione'].'
            </div>';
        }
    echo '
    </div>
    <div class="row">
        <div class="col-md-12 text-right" style="margin-top:15px;">
            <button type="submit" class="btn btn-success"><i class="fa fa-plus"></i>'.tr('Aggiungi').'</button>
        </div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';
