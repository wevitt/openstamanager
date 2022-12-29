<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="form-edit-riga">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_articolo">
    <input type="hidden" name="idriga" value="'.$riga->id.'">';

// Prezzo di vendita unitario
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di vendita').'", "name": "prezzo_unitario", "value": "'.$riga->prezzo_unitario_corrente.'", "required": 1, "icon-after": "'.currency().'", "help": "'.tr('Importo IVA inclusa').'", "extra": "inputmode=\"numeric\" pattern=\"[0-9]*\"" ]}
        </div>';

// Prezzo di acquisto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di acquisto').'", "name": "costo_unitario", "value": "'.$riga->costo_unitario.'", "required": 1, "icon-after": "'.currency().'", "extra": "inputmode=\"numeric\" pattern=\"[0-9]*\"" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'", "help": "'.tr('Lo sconto viene applicato sull\'imponibile. Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'", "extra": "inputmode=\"numeric\" pattern=\"[0-9]*\"" ]}
        </div>';

// Iva
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$riga->idiva.'", "ajax-source": "iva" ]}
        </div>

        <div class="col-md-4"></div>

        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Reparto').'", "name": "id_reparto", "value": "'.($riga->id_reparto).'", "values": "query=SELECT id, CONCAT(codice, \' - \', descrizione) AS descrizione FROM vb_reparti" ]}
        </div>
    </div>';

// Tastiera
echo '
    <div class="row">
        <div class="col-md-12" id="keyboard">
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
        </div>
    </div>
    <div class="clearfix"></div>';

//PULSANTE
echo '
	<div class="row">
		<div class="col-md-12 text-right">
			<a class="btn btn-primary pull-right" onclick="edit_riga();">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</a>
		</div>
    </div>
</form>';

echo '
<script>
    $(document).ready(init);

    function edit_riga(){
        form = $("#form-edit-riga");
        $.ajax({
            url: globals.rootdir + "/actions.php?id_module='.Modules::get('Vendita al banco')['id'].'" ,
            type: "POST",
            data:  form.serialize(),
            success: function(data) {
                setTimeout(function(){
                    caricaContenuti();
                },300);
                $(".close").trigger("click");
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio delle informazioni').'", "error");
            }
        });

    }

</script>';
