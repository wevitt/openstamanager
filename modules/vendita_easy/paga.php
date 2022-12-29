<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

$metodi_standard = $dbo->table('zz_settings')->where('nome','Metodi di pagamento standard')->first();
$metodi_standard = explode(',',$metodi_standard->valore);

echo '
<div class="alert alert-info">
    <p>'.tr('Verrà salvato il pagamento indicato e stampato lo scontrino').'</p>
</div>

<form action="" method="post" id="form-paga">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="panel-body">';
        if( !empty($metodi_standard) ){

            echo'
            <div class="row" style="max-height:200px;overflow:auto;">';

            foreach($metodi_standard as $metodo){
                $pagamento = $dbo->table('co_pagamenti')->where('descrizione',$metodo)->first();

                echo '
                <div class="col-md-4">
                    <a type="button" class="btn btn-lg btn-primary" style="width:100%;" onclick="$(\'#idpagamento\').val('.$pagamento->id.').trigger(\'change\');">'.$pagamento->descrizione.'</a>
                    <br><br>
                </div>';
            }

            echo '
            </div>';
        }
echo '
        <div class="row">
            <div class="col-md-12">';

$pagamenti = $dbo->fetchArray('SELECT id, descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL ORDER BY descrizione ASC');
if (!empty($pagamenti)) {
    echo '
                {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "idpagamento", "required": 1, "values": "query=SELECT id, tipo_xon_xoff, CONCAT(descrizione, IF(tipo_xon_xoff, CONCAT(\' (\', tipo_xon_xoff, \')\'), \'\')) AS descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL AND (tipo_xon_xoff IS NOT NULL OR tipo_xon_xoff=\'\') ORDER BY descrizione ASC", "value": "'.(!empty($documento->idpagamento) ? $documento->idpagamento : setting('Pagamento predefinito')).'", "readonly": "'.$is_pagato.'" ]}';
} else {
    echo '
                <div class="alert alert-danger">
                    '.tr('Nessun tipo di pagamento ha un conto impostato. Questo non permetterà di eseguire la chiusura della vendita correttamente.').' '.Modules::link('Pagamenti', null, tr('Collega almeno un tipo di pagamento ad un conto'), null).'
                </div>';
}

echo '
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                {["type":"text", "label":"'.tr('Codice lotteria').'", "name":"codice_lotteria", "value":"", "placeholder":"'.tr('Codice lotteria').'" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                {["type":"checkbox", "label":"'.tr('Chiudi la vendita').'", "name":"closed", "value":"'.setting('Chiusura vendita alla stampa scontrino').'" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "number", "label": "'.tr('Importo pagato').'", "name": "importo_pagato" ]}
                <span id="resto"></span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-right">
                <a class="btn btn-primary" onclick="paga()">
                    <i class="fa fa-money"></i> '.tr('Paga').'
                </a>
            </div>
        </div>
    </span>
</form>

<script>
    $(document).ready(init);

    $("#form-paga").keydown(function (e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            return false;
        }
    });

    function paga(){
        var closed = null;

        if( $("#closed").is(":checked") ){
            closed = 1;
        }

        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.id_module ,
            type: "POST",
            data: {
                id_module: '.Modules::get('Vendita al banco')['id'].',
                id_record: globals.id_record,
                op: "update",
                idanagrafica: "'.$documento->idanagrafica.'",
                idpagamento: $("#idpagamento").val(),
                importo_pagato: $("#importo_pagato").val(),
                closed: closed,
            },
            success: function(data) {
                $(".close").trigger("click");
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio delle informazioni').'", "error");
            }
        });

        $.ajax({
            url: globals.rootdir + "/actions.php?id_module='.Modules::get('Vendita al banco')['id'].'",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: '.Modules::get('Vendita al banco')['id'].',
                id_record: globals.id_record,
                op: "stampa",
                formato: "xonxoff",
                fiscale: 1,
                codice_lotteria: $("#codice_lotteria").val(),
            },
            success: function (response) {
                if (!response.result) {
                    swal("'.tr('Errore').'", response.message, "error");
                }else{
                    swal("'.tr('Invio completato').'", response.message, "success").then(() => { 
                        if( "'.get('ritorna').'" == 1 ){
                            window.location.href = globals.rootdir + "/editor.php?id_module='.Modules::get('Easy vendita')['id'].'";
                        }
                    });
                }
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nella stampa del documento').'", "error");
            }
        });
	}

	var importo = $("#importo_pagato");
	var resto = $("#resto");
    var totale = '.$documento->totale.';

	var pagamento = input("idpagamento");

	pagamento.change(function() {
	    const abilitaImporto = pagamento.getData().tipo_xon_xoff == "1T";

	    if (abilitaImporto) {
	        importo.removeClass("disabled").attr("disabled", false);
	        resto.removeClass("hidden");
	    } else {
	        importo.addClass("disabled").attr("disabled", true);
            resto.addClass("hidden");
            input("importo_pagato").set(totale);
	    }
	});

	importo.change(function() {
	    const importoValore = $(this).val().toEnglish();
	    const restoValore = importoValore - totale

	    resto.html("Resto atteso: " + restoValore.toLocale() + " " + globals.currency)
	});

	// Trigger per aggiornamento resto
	$(document).ready(function() {
	    pagamento.trigger("change");
	});
</script>';
