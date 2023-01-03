<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_articolo">
    <input type="hidden" name="idriga" value="'.$riga->id.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-8">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "value": "'.$riga->descrizione.'", "required": 1]}
        </div>';

// Iva
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$riga->idiva.'", "ajax-source": "iva" ]}
        </div>
    </div>';

// Prezzo di acquisto unitario
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di acquisto').'", "name": "costo_unitario", "value": "'.$riga->costo_unitario.'", "required": 1, "icon-after": "'.currency().'" ]}
        </div>';

// Prezzo di vendita unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di vendita ivato').'", "name": "prezzo_unitario", "value": "'.$riga->prezzo_unitario_corrente.'", "required": 1, "icon-after": "'.currency().'", "help": "'.tr('Importo IVA inclusa').'" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'", "help": "'.tr('Lo sconto viene applicato sull\'imponibile. Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
		</div>
    </div>
</form>

<script>$(document).ready(init)</script>';
