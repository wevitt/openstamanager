<?php

include_once __DIR__.'/../../core.php';

$user = Auth::user();
$_SESSION['superselect']['idsede_partenza'] = $documento->idmagazzino;
$_SESSION['superselect']['idanagrafica'] = $user->idanagrafica;

echo '<form action="" method="post" role="form">
	<input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" id="closed" name="closed" value="'.($is_pagato).'">
	<input type="hidden" name="id_record" value="'.$id_record.'">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Intestazione').'</h3>
		</div>

		<div class="panel-body">

			<div class="row">

				<div class="col-md-2">
					{[ "type": "text", "label": "'.tr('Numero').'", "name": "numero_esterno", "required": 1, "class": "text-center", "value": "$numero_esterno$", "readonly": "1" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "'.tr('Data e ora').'", "name": "data", "required": 1, "class": "text-center", "value": "$data$", "readonly": "1" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "select", "label": "'.tr('Stato').'", "name": "idstato", "required": 1, "class": "", "values": "query=SELECT id,descrizione FROM vb_stati_vendita ORDER BY descrizione ASC", "value": "$idstato$", "readonly": "1" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Magazzino').'", "name": "idmagazzino", "required": 1, "ajax-source": "sedi_azienda", "value": "$idmagazzino$", "readonly": "'.($is_pagato || $numero_righe != 0).'" ]}
				</div>

				<div class="col-md-3">';

$rs_pagamenti = $dbo->fetchArray('SELECT id, descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL ORDER BY descrizione ASC');
if (!empty($rs_pagamenti)) {
    echo '
					{[ "type": "select", "label": "'.tr('Pagamento').'", "name": "idpagamento", "required": 0, "class": "", "values": "query=SELECT id, tipo_xon_xoff, CONCAT(descrizione, IF(tipo_xon_xoff, CONCAT(\' (\', tipo_xon_xoff, \')\'), \'\')) AS descrizione FROM co_pagamenti WHERE idconto_vendite IS NOT NULL AND (tipo_xon_xoff IS NOT NULL OR tipo_xon_xoff=\'\') ORDER BY descrizione ASC", "value": "'.(!empty($documento->idpagamento) ? $documento->idpagamento : setting('Pagamento predefinito')).'", "readonly": "'.$is_pagato.'" ]}';
} else {
    echo '
					<div class="alert alert-danger">'.tr('Nessun tipo di pagamento ha un conto impostato. Questo non permetterà di eseguire la chiusura della vendita correttamente.').' '.Modules::link('Pagamenti', null, tr('Collega almeno un tipo di pagamento ad un conto'), null).'</div>';
}

echo '
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "ajax-source": "clienti", "value": "$idanagrafica$", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica= '.$tipo_anagrafica.'" ]}
				</div>

                <div class="col-md-5">
                    {[ "type": "number", "label": "'.tr('Importo pagato').'", "name": "importo_pagato", "value": "$importo_pagato$" ]}
                </div>

                <div class="col-md-4" style="margin-top: 27px" id="resto">
                </div>
			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "'.tr('Note').'", "name": "note", "required": 0, "class": "", "value": "$note$", "readonly": "'.$is_pagato.'" ]}
				</div>
			</div>

		</div>
	</div>
</form>

<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">'.tr('Righe').'</h3>
	</div>

	<div class="panel-body">';

// Form di inserimento riga documento
if (!$is_pagato) {
    echo '
		<form id="link_form" action="" method="post">
			<input type="hidden" name="op" value="add_articolo">
			<input type="hidden" name="backto" value="record-edit">

			<div class="row">
				<div class="col-md-3">
					{[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite barcode').'", "name": "barcode", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-barcode\"></i>", "required": 0 ]}
				</div>

                <div class="col-md-3">
                    {[ "type": "file", "label": "' . tr('Inserisci barcode file') . '", "id": "barcode_file", "name": "barcode_file", "value": "", "icon-before": "<i class=\"fa fa-file\"></i>", "multiple": true ]}
                </div>

				<div class="col-md-3">
					{[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite codice').'", "name": "codice", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-th-large\"></i>", "required": 0 ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "value": "", "ajax-source": "articoli", "icon-after": "add|'.Modules::get('Articoli')['id'].'" ]}
				</div>

				<div class="col-md-3 col-md-offset-9" style="margin-bottom: 20px">
                    <button title="'.tr('Aggiungi articolo alla vendita').'" class="btn btn-primary tip" type="button" onclick="salvaArticolo()">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>';

                    // Aggiunta riga libera
                    echo '
                    <a class="btn btn-primary" data-href="'.$module->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_riga" data-toggle="tooltip" data-title="'.tr('Aggiungi riga').'">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </a>';

                    // Aggiunta sconto o descrizione
                    echo '
                    <div class="btn-group tip" data-toggle="tooltip">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <i class="fa fa-list"></i> '.tr('Altro').'
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li>
                                <a data-href="'.$module->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_descrizione" data-toggle="tooltip" data-title="'.tr('Aggiungi descrizione').'">
                                    <i class="fa fa-plus"></i> '.tr('Descrizione').'
                                </a>
                            </li>

                            <li>
                               <a data-href="'.$module->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&is_sconto" data-toggle="tooltip" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                                    <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                                </a>
                            </li>
                        </ul>
                    </div>';
                 echo '
				</div>
			</div>
		</form>';
}

echo '
		<div id="row-list">';

include $structure->filepath('row-list.php');

echo '
		</div>
	</div>
</div>';

echo '
<a class="btn btn-danger ask '.($is_pagato ? 'disabled' : '').'" data-backto="record-list" '.($is_pagato ? 'disabled' : '').'>
	<i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';

echo '
<script>
    function salvaArticoloFile(barcode, qta) {
        $.ajax({
			url: globals.rootdir + "/actions.php",
			data: {
				id_module: globals.id_module,
				id_record: globals.id_record,
				idmagazzino : ' . $documento->idmagazzino . ',
				ajax: true,
                op: "add_articolo",
                backto: "record-edit",
                barcode: barcode,
                qta: qta,
                id_anagrafica: $(\'select[name="idanagrafica"]\').val(),
			},
			type: "post",
			beforeSubmit: function(arr, $form, options) {
				return $form.parsley().validate();
			},
			success: function(response){
                if( response.length>0 ){
					swal({
						type: "error",
						title: "'.tr('Errore').'",
						text:  "'.tr('Nessun articolo corrispondente a magazzino').'",
					});
				}

				$("#barcode").val("");
				$("#codice").val("");
				$("#id_articolo").selectReset();
				reloadRows();
			}
		});
    }

	function salvaArticolo(){
		$("#link_form").ajaxSubmit({
			url: globals.rootdir + "/actions.php",
			data: {
				id_module: globals.id_module,
				id_record: globals.id_record,
				idmagazzino : '.$documento->idmagazzino.',
				ajax: true,
                id_anagrafica: $(\'select[name="idanagrafica"]\').val(),
			},
			type: "post",
			beforeSubmit: function(arr, $form, options) {
				return $form.parsley().validate();
			},
			success: function(response){
				if( response.length>0 ){
					swal({
						type: "error",
						title: "'.tr('Errore').'",
						text:  "'.tr('Nessun articolo corrispondente a magazzino').'",
					});
				}

				$("#barcode").val("");
				$("#codice").val("");
				$("#id_articolo").selectReset();
				reloadRows();
			}
		});
	}

	function reloadRows() {
        return $.ajax({
            url: "'.$module->fileurl('row-list.php').'",
            type: "GET",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
            },
            success: function (response) {
                $("#row-list").html(response);

                if ($("#row-list .table tr").length > 3) {
                    $("#idmagazzino").attr("disabled", true);
                    $("#btn-close").attr("disabled", false);
                } else {
                    $("#idmagazzino").attr("disabled", false);
                    $("#btn-close").prop("disabled", true);
                }

                restart_inputs();
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nel caricamento delle righe del documento').'", "error");
            }
        });
   	}

	$(document).ready( function(){
        $("#barcode_file").on("change", function (event) {
            let files = event.target.files;
            //read csv file
            let reader = new FileReader();
            reader.readAsText(files[0]);
            reader.onload = function (event) {
                var csv = event.target.result;
                var lines = csv.split("\n");
                lines.forEach(function (line) {
                    if (line === "") {
                        return;
                    }
                    var barcode = line.split(";")[0];
                    var qta = line.split(";")[1];
                    barcode = barcode.replace(/\//g, "-");

                    salvaArticoloFile(barcode, qta);
                });
            };
        });

		$("#id_articolo").on("change", function(e) {
			if ($(this).val()) {
				var data = $(this).selectData();

				if (data.barcode) {
					$("#barcode").val(data.barcode);
				} else {
					$("#codice").val(data.codice);
				}
			}

			e.preventDefault();

			setTimeout(function(){
				$("#barcode").focus();';

if (setting('Aggiungere automaticamente articolo alla vendita quando selezionato')) {
    echo '
					if ( $("#barcode").val() || $("#codice").val()){
						salvaArticolo();
					}';
}
echo '
            }, 100);
		});

		$("#barcode").focus();
	} );

	$("form").bind("keypress", function(e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			salvaArticolo();
			return false;
		}
	});

	const importo = $("#importo_pagato");
	const resto = $("#resto");
	const pagamento = input("idpagamento");

	pagamento.change(function() {
	    const abilitaImporto = pagamento.getData().tipo_xon_xoff == "1T";

	    if (abilitaImporto) {
	        importo.removeClass("disabled").attr("disabled", false);
	        resto.removeClass("hidden");
	    } else {
	        importo.addClass("disabled").attr("disabled", true);
            resto.addClass("hidden");
	    }
	});

	importo.change(function() {
	    const importoValore = $(this).val().replace(".", ",").toEnglish();
        const totale = parseFloat($("#totale_documento").text());
	    const restoValore = importoValore - totale

	    resto.html("Resto atteso: " + restoValore.toLocale() + " " + globals.currency)
	});

	// Trigger per aggiornamento resto
	$(document).ready(function() {
	    pagamento.trigger("change");
	});
</script>';
