<?php

include_once __DIR__.'/../../core.php';

$user = Auth::user();
$_SESSION['superselect']['idsede_partenza'] = $documento->idmagazzino;
$_SESSION['superselect']['idanagrafica'] = $user->idanagrafica;

if (empty($_SESSION['superselect']['categoria_vendita'])) {
    $_SESSION['superselect']['categoria_vendita'] = $categorie[0]['id'];
}

if ($articolo->qta <= 0 || $documento->isPagato() || !empty($documento->iddocumento) || !empty($documento->data_emissione)) {
    $disabled = 'disabled';
}

echo '
<link rel="stylesheet" type="text/css" media="all" href="'.$structure->fileurl('assets/dist/css/style.css').'"/>';

?>

<?php

echo '
<div class="panel-body pagina">
	<!--INTESTAZIONE-->
	<div class="row">
		<div class="col-md-4">
			<a type="button" class="btn btn-warning" href="'.$rootdir.'/controller.php?id_module='.$id_module.'"><i class="fa fa-chevron-left"></i> '.tr('Torna all\'elenco').'</a>
		</div>
		<div class="col-md-4 text-center text-intestazione" >
			<span>'.tr('Ordine N°_NUM_',
        ['_NUM_' => $documento->numero]).'</span>
		</div>
		<div class="col-md-4 text-right text-intestazione" >
			<span>'.tr('OPERATORE').': '.$user->username.'</span>
		</div>
	</div>

	<hr>

	<!--CORPO-->
	<div class="row">
        <div class="col-md-2 text-center" style="z-index: 1;max-height:600px;overflow:auto;">
			<h5><b>'.tr('CATEGORIE').'</b></h5>
			<div id="list-categories">';
$i = 0;
foreach ($categorie as $categoria) {
    $color = ($categoria['id'] == $_SESSION['superselect']['categoria_vendita'] ? 'btn-primary ' : 'btn-default ');

    echo '
				<a class="btn btn-block btn-categoria '.$color.'" id="'.$categoria['id'].'">'.$categoria['nome'].'</a>';
    ++$i;
}
echo '
			</div>
		</div>
		<div class="col-md-4 text-center div-articoli" id="ajax_articoli" style="z-index: 5;"></div>';

echo '
		<div class="col-md-6 text-center">
			<div class="col-md-12 text-center" id="row-list"></div>
			<div class="col-md-12 text-center" id="div_costi"></div>
		</div>
	</div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Barcode Reader').'", "name":"barcode", "id": "barcode_reader", "icon-before": "<i class=\"fa fa-barcode\"></i>" ]}
        </div>
    </div>

	<div class="row text-center" id="pulsanti_bottom"></div>
</div>

<script type="text/javascript">

    $(document).ready(function(){
        setTimeout(function(){
            $("#barcode_reader").focus();
        });
    });

    $("form").bind("keypress", function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            salvaArticolo();
            return false;
        }
    });

    $("#barcode_reader").change(function(){
        salvaArticolo();
    });

    function salvaArticolo(){
        
        $.ajax({
            url: globals.rootdir + "/actions.php" ,
            type: "POST",
            data: {
                id_module: globals.id_module,
				id_record: globals.id_record,
				idmagazzino : '.$documento->idmagazzino.',
				ajax: true,
                op: "add_articolo",
                barcode: $("#barcode_reader").val(),
            },
            success: function(response) {
                response = JSON.parse(response);
                if( !response.result ){
					swal({
						type: "error",
						title: "'.tr('Errore').'",
						text:  "'.tr('Nessun articolo corrispondente a magazzino').'",
					});
				}

                $("#barcode_reader").val("");
                caricaContenuti();
            },
            error: function() {
                swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio delle informazioni').'", "error");
            }
        });
	}

 	globals.vendita_easy = {
         id_modulo_vendita: "'.Modules::get('Vendita al banco')['id'].'",
         barcode: "'.tr('Inserisci un barcode!').'",
         urls: {
            righe: "'.$module->fileurl('row-list.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
            articoli: "'.$module->fileurl('ajax_articoli.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
            pulsanti: "'.$module->fileurl('ajax_buttons.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
            costi: "'.$module->fileurl('ajax_costi.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
         },
         rimuoviRighe: {
             titolo: "'.tr('Rimuovere tutte le righe?').'",
             messaggio: "'.tr("Sei sicuro di voler rimuovere tutte le rige del documento? L'operazione è irreversibile.").'",
             conferma: "'.tr('Elimina').'",
        },
        chiusuraFiscale: {
             titolo: "'.tr('Chiusura fiscale').'...",
             url: "'.$module->fileurl('close_modal.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
        },
        riaperturaFiscale: {
             titolo: "'.tr('Riapri vendita?').'",
             messaggio: "'.tr('Sei sicuro di voler riaprire questa vendita?').'",
             conferma: "'.tr('Apri').'",
             errore: "'.tr('Attenzione').'",
        },
        errore: {
            titolo: "'.tr('Errore').'",
            messaggio: "'.tr('Errore nel salvataggio delle informazioni').'",
        },
        stampa: {
            conferma: "'.tr('Sì').'",
            errore: "'.tr('Errore nella stampa del documento').'",
            preconto: {
                titolo: "'.tr('Stampare preconto?').'",
                messaggio: "'.tr('Sei sicuro di volere stampare il preconto?').'",
            },
            comanda: {
                titolo: "'.tr('Stampare comanda?').'",
                messaggio: "'.tr('Sei sicuro di volere stampare la comanda?').'",
            },
            scontrino: {
                titolo: "'.tr('Stampare scontrino fiscale?').'",
                messaggio: "'.tr('Sei sicuro di volere stampare lo scontrino fiscale?').'",
            },
            lotteria: {
                titolo: "'.tr('Stampare scontrino fiscale con lotteria?').'",
                messaggio: "",
            },
        },
        gestioneRighe: {
            url: "'.$module->fileurl('row-add.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record,
            sconto: "'.tr('Aggiungi sconto').'",
            descrizione: "'.tr('Aggiungi descrizione').'",
        },
        aperturaCassetto: {
            titolo: "'.tr('Cassetto aperto!').'",
            messaggio: "'.tr('Cassetto aperto con successo!').'",
        },
        aggiungiBarcode: {
            titolo: "'.tr('Articolo non trovato!').'",
            messaggio: "'.tr('Non è stato trovato nessun articolo con il barcode indicato!').'",
        },
 	};
</script>
<script src="'.$module->fileurl('script.js').'"></script>';
