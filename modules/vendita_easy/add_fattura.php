<?php

include_once __DIR__.'/../../core.php';

$module = Modules::get($id_module);
$dir = 'entrata';
$tipo_anagrafica = tr('Cliente');

$calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");

$id_rivalsa_inps = setting('Percentuale rivalsa');
$show_rivalsa = !empty($id_rivalsa_inps);
$show_ritenuta_acconto = setting("Percentuale ritenuta d'acconto") != '' || !empty($id_ritenuta_acconto);
$show_ritenuta_contributi = !empty($documento_finale['id_ritenuta_contributi']);

$id_conto = $documento_finale['idconto'];
if (empty($id_conto)) {
    $id_conto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

?>
<form action="" method="post" id="add-fattura">
	<input type="hidden" name="op" value="fattura_vendita">
	<input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">


	<div class="row">

        <?php
            if ($dir == 'uscita') {
                echo '
            <div class="col-md-3">
                {[ "type": "text", "label": "'.tr('N. fattura del fornitore').'", "required": 1, "name": "numero_esterno","class": "text-center", "value": "" ]}
            </div>';
                $size = 3;
            } else {
                $size = 6;
            }
        ?>

		<div class="col-md-<?php echo $size; ?>">
			 {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "-now-" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo $tipo_anagrafica; ?>", "name": "idanagrafica", "id": "idanagrafica_add", "required": 1, "ajax-source": "clienti", "value": "", "icon-after": "add|<?php echo Modules::get('Anagrafiche')['id']; ?>|tipoanagrafica=<?php echo $tipo_anagrafica; ?>" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Tipo fattura'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir; ?>'" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "<?php echo tr('Sezionale'); ?>", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='<?php echo MODULES::get('Fatture di vendita')['id']; ?>' ORDER BY name", "value": "" ]}
		</div>
	</div>

<?php
    if ($show_rivalsa || $show_ritenuta_acconto) {
        echo '
            <div class="row">';

        // Rivalsa INPS
        if ($show_rivalsa) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.(($options['dir'] == 'entrata') ? setting('Tipo Cassa Previdenziale') : null).'" ]}
                </div>';
        }

        // Ritenuta d'acconto
        if ($show_ritenuta_acconto) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
                </div>';

            // Calcola ritenuta d'acconto su
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\"", "required": "1" ]}
                </div>';
        }

        echo '
            </div>';
    }
?>
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "<?php echo tr('Ritenuta contributi'); ?>", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
        </div>

<?php // Ritenuta contributi
        if ($show_ritenuta_contributi) {
            echo '
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Ritenuta contributi').'", "name": "ritenuta_contributi", "value": "1" ]}
                </div>';
        }
?>

        <div class="col-md-4">
            {[ "type": "select", "label": "<?php echo tr('Conto'); ?>", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "<?php echo $dir == 'entrata' ? 'conti-vendite' : 'conti-acquisti'; ?>" ]}
        </div>
    </div>

<?php if ($show_rivalsa) {
    echo '
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.(($options['dir'] == 'entrata') ? setting('Tipo Cassa Previdenziale') : null).'" ]}
                </div>
            </div>';
}
?>

    <div class="box box-warning hidden" id="info">
        <div class="box-header with-border">
            <h3 class="box-title"><?php echo tr('Fatture in stato Bozza del cliente'); ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body" id="info-content">
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-primary" onclick="crea_fattura();"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?></button>
		</div>
	</div>
</form>

<?php

if ($dir == 'entrata') {
    echo '
<script>
$(document).ready(function () {
    init();
    $("#idanagrafica_add").change(function () {
        var data = $(this).selectData();

        if (data !== undefined) {
            if (!data.id){
                $("#info").addClass("hidden");
                return;
            }

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    id_module: globals.id_module,
                    id_anagrafica: data.id,
                    op: "fatture_bozza",
                },
                success: function (results) {
                    $("#info").removeClass("hidden");

                    if (results.length === 0){
                        $("#info-content").html("<p>'.tr('Nessuna fattura in stato Bozza presente per il cliente corrente').'</p>")
                    } else {
                        var content = "";

                        results.forEach(function(item) {
                            content += "<li>" + item + "</li>";
                        });

                        $("#info-content").html("<p>'.tr('Sono presenti le seguenti fatture in stato Bozza per il cliente corrente').':</p><ul>" + content + "</ul>")
                    }
                }
            });
        }
    })
})

function crea_fattura(){
    form = $("#add-fattura");
    $.ajax({
        url: globals.rootdir + "/actions.php?id_module=" + globals.id_module ,
        type: "POST",
        data:  form.serialize(),
        success: function(data) {
            setTimeout(function(){
                caricaContenuti();
                $(\'#ajax_articoli\').html("<i class=\"fa fa-gear fa-spin\"></i> '.tr('Caricamento').'...");
                $(\'#ajax_articoli\').load(\''.$rootdir.'/modules/vendita_easy/ajax_articoli.php?id_record=\' + globals.id_record);
            },300);
            $(".close").trigger("click");
        },
        error: function() {
            swal("'.tr('Errore').'", "'.tr('Errore nel salvataggio delle informazioni').'", "error");
        }
    });
}

</script>';
}
