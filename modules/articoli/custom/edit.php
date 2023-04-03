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

include_once __DIR__.'/../../core.php';

use Modules\Iva\Aliquota;

?>

<form action="" method="post" id="edit-form" enctype="multipart/form-data">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI ANAGRAFICI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Articolo'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-3">
					{[ "type": "image", "label": "<?php echo tr('Immagine'); ?>", "name": "immagine", "class": "img-thumbnail", "value": "<?php echo $articolo->image; ?>", "accept": "image/x-png,image/gif,image/jpeg" ]}
				</div>

                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Codice'); ?>", "name": "codice", "required": 1, "value": "$codice$", "validation": "codice" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "text", "label": "<?php echo tr('Barcode'); ?>", "name": "barcode", "value": "$barcode$" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?php echo Modules::link('Categorie articoli', $record['id_categoria'], null, null, 'class="pull-right"'); ?>
                            {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 0, "value": "$id_categoria$", "ajax-source": "categorie", "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "subcategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie", "select-options": <?php echo json_encode(['id_categoria' => $record['id_categoria']]); ?>, "icon-after": "add|<?php echo Modules::get('Categorie articoli')['id']; ?>|id_original=<?php echo $record['id_categoria']; ?>" ]}
                        </div>
                    </div>
                </div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$", "charcounter": 1 ]}
				</div>
			</div>

            <div class="row">
                <div class="col-md-6">
					{[ "type": "checkbox", "label": "<?php echo tr('Abilita serial number'); ?>", "name": "abilita_serial", "value": "$abilita_serial$", "help": "<?php echo tr('Abilita serial number in fase di aggiunta articolo in fattura o ddt'); ?>", "placeholder": "<?php echo tr('Serial number'); ?>", "extra": "<?php echo ($record['serial'] > 0) ? 'readonly' : ''; ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<?php echo tr('Attivo'); ?>", "name": "attivo", "help": "<?php echo tr('Seleziona per rendere attivo l\'articolo'); ?>", "value": "$attivo$", "placeholder": "<?php echo tr('Articolo attivo'); ?>" ]}
                </div>

            </div>

			<div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Ubicazione'); ?>", "name": "ubicazione", "value": "$ubicazione$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "<?php echo tr('Unità di misura'); ?>", "name": "um", "value": "$um$", "ajax-source": "misure", "icon-after": "add|<?php echo Modules::get('Unità di misura')['id']; ?>" ]}
                </div>
            </div>

            <div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">
                <?php echo tr('Giacenza totale'); ?>
            </h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "number", "label": "<?php echo tr('Quantità'); ?>", "name": "qta", "required": 1, "value": "$qta$", "readonly": 1, "decimals": "qta", "min-value": "undefined", "icon-after": "<?php echo (!empty($record['um']) ? $record['um']: ''); ?>" ]}
                    <input type="hidden" id="old_qta" value="<?php echo $record['qta']; ?>">
                </div>

                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "<?php echo tr('Modifica quantità'); ?>", "name": "qta_manuale", "value": 0, "help": "<?php echo tr('Seleziona per modificare manualmente la quantità'); ?>", "placeholder": "<?php echo tr('Quantità manuale'); ?>", "extra": "<?php echo ($record['servizio']) ? 'disabled' : ''; ?>" ]}
                </div>
            </div>

            <div class='row' id="div_modifica_manuale" style="display:none;">
                <div class='col-md-8'>
                    {[ "type": "text", "label": "<?php echo tr('Descrizione movimento'); ?>", "name": "descrizione_movimento" ]}
                </div>
                <div class='col-md-4'>
                    {[ "type": "date", "label": "<?php echo tr('Data movimento'); ?>", "name": "data_movimento", "value": "-now-" ]}
                </div>
            </div>

            <div class="alert alert-info">
                <p><?php echo tr('Le modifiche alle quantità in questa schermata prevedono la generazione di un movimento relativo alla sede legale'); ?>. <?php echo tr('Se si desidera effettuare movimenti per altre sedi, utilizzare il modulo _MODULO_ ', [
                    '_MODULO_' => Modules::link('Movimenti', null, tr('Movimenti'))
                ]); ?>.</p>
            </div>

            <script type="text/javascript">
                $(document).ready(function() {
                    $('#servizio').click(function() {
                        $("#qta_manuale").attr("disabled", $('#servizio').is(":checked"));
                    });

                    $('#qta_manuale').click(function() {
                        $("#qta").attr("readonly", !$('#qta_manuale').is(":checked"));
                        if($('#qta_manuale').is(":checked")){
                            $("#div_modifica_manuale").show();
                            $("#div_modifica_manuale").show();
                            $("#descrizione_movimento").attr('required', true);
                            $("#data_movimento").attr('required', true);
                        }else{
                            $("#div_modifica_manuale").hide();
                            $('#qta').val($('#old_qta').val());
                            $("#descrizione_movimento").attr('required', false);
                            $("#data_movimento").attr('required', false);
                        }
                    });

                });
            </script>
        </div>
    </div>

    <!-- informazioni Acquisto/Vendita -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?php echo tr('Acquisto'); ?></h3>
                </div>

                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Prezzo di acquisto'); ?>", "name": "prezzo_acquisto", "value": "$prezzo_acquisto$", "icon-after": "<?php echo currency(); ?>", "help": "<?php echo tr('Prezzo di acquisto previsto per i fornitori i cui dati non sono stati inseriti nel plugin Fornitori'); ?>." ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Coefficiente di vendita'); ?>", "name": "coefficiente", "value": "$coefficiente$", "help": "<?php echo tr('Imposta un coefficiente per calcolare automaticamente il prezzo di vendita quando cambia il prezzo di acquisto'); ?>." ]}
                        </div>

                        <!--<div class="col-md-4">
                            {[ "type": "number", "label": "<?php echo tr('Soglia minima quantità'); ?>", "name": "threshold_qta", "value": "$threshold_qta$", "decimals": "qta", "min-value": "undefined" ]}
                        </div>-->
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="tbl_soglia_minima"><?php echo tr('Soglia minima per sede') ?></label>
                            <table id="tbl_soglia_minima" class="table table-striped table-condensed table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo tr('Sede') ?></th>
                                        <th width="20%" class="text-center"><?php echo tr('Soglia minima') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo $articoloSedeLegale['nomesede'] ?></td>
                                        <td>
                                            {[ "type": "number", "name": "threshold_qta_sedi[<?php echo $articoloSedeLegale['id_sede'] ?>]", "value": "<?php echo $articoloSedeLegale['threshold_qta'] ?? 0 ?>", "decimals": "qta", "min-value": "0" ]}
                                        </td>
                                    </tr>
                                    <?php foreach ($articoloSedi as $articoloSede) { ?>
                                    <tr>
                                        <td><?php echo $articoloSede['nomesede'] ?></td>
                                        <td>
                                            {[ "type": "number", "name": "threshold_qta_sedi[<?php echo $articoloSede['id_sede'] ?>]", "value": "<?php echo $articoloSede['threshold_qta'] ?? 0 ?>", "decimals": "qta", "min-value": "0" ]}
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            {[ "type": "select", "label": "<?php echo tr('Fornitore predefinito'); ?>", "name": "id_fornitore", "ajax-source": "fornitori-articolo", "select-options": <?php echo json_encode(['id_articolo' => $id_record]); ?>, "value":"$id_fornitore$", "help": "<?php echo tr('Fornitore predefinito selezionabile tra i fornitori presenti nel plugin \"Listino fornitori\"'); ?>." ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            {[ "type": "select", "label": "<?php echo tr('Conto predefinito di acquisto'); ?>", "name": "idconto_acquisto", "value": "$idconto_acquisto$", "ajax-source": "conti-acquisti" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            {[ "type": "select", "label": "<?php echo tr('U.m. secondaria'); ?>", "name": "um_secondaria", "value": "$um_secondaria$", "ajax-source": "misure", "help": "<?php echo tr("Unità di misura da utilizzare nelle stampe di Ordini fornitori in relazione all'articolo"); ?>" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "number", "label": "<?php echo tr('Fattore moltiplicativo'); ?>", "name": "fattore_um_secondaria", "value": "$fattore_um_secondaria$", "decimals": "qta", "help": "<?php echo tr("Fattore moltiplicativo per l'unità di misura da utilizzare nelle stampe di Ordini fornitori"); ?>" ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "number", "label": "<?php echo tr('Q.tà multipla'); ?>", "name": "qta_multipla", "value": "$qta_multipla$", "decimals": "qta", "help": "<?php echo tr('Quantità multipla di scorta da tenere a magazzino.'); ?>" ]}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?php echo tr('Vendita'); ?>
                    </h3>
                </div>

                <div class="panel-body">
                    <div class="clearfix"></div>

                    <div class="row">
                        <div class="col-md-6">
<?php
$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
$iva_predefinita = setting('Iva predefinita');
$aliquota_predefinita = floatval(Aliquota::find($iva_predefinita)->percentuale);
if (empty($prezzi_ivati)) {
    echo '
                            <button type="button" class="btn btn-info btn-xs pull-right tip pull-right '.(!empty((int)$articolo->coefficiente) ? 'disabled' : '').'" title="'.tr('Scorpora l\'IVA dal prezzo di vendita.').'" id="scorporaIva">
                                <i class="fa fa-calculator"></i>
                            </button>';
}

echo '
                            {[ "type": "number", "label": "'.tr('Prezzo di vendita').'", "name": "prezzo_vendita", "value": "'.($prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita).'", "icon-after": "'.currency().'", "help": "'.($prezzi_ivati ? tr('Importo IVA inclusa') : '').'", "disabled": "'.(!empty((int)$articolo->coefficiente) ? 1 : 0).'" ]}
                        </div>';

?>

                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Iva di vendita'); ?>", "name": "idiva_vendita", "ajax-source": "iva", "value": "$idiva_vendita$", "help": "<?php echo tr('Se non specificata, verrà utilizzata l\'iva di default delle impostazioni'); ?>" ]}
                            <input type="hidden" name="prezzi_ivati" value="<?php echo $prezzi_ivati; ?>">
                            <input type="hidden" name="aliquota_predefinita" value="<?php echo $aliquota_predefinita; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Garanzia'); ?>", "name": "gg_garanzia", "decimals": 0, "value": "$gg_garanzia$", "icon-after": "GG" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "checkbox", "label": "<?php echo tr('Questo articolo è un servizio'); ?>", "name": "servizio", "value": "$servizio$", "help": "<?php echo tr('Le quantità non saranno considerate.'); ?>", "placeholder": "<?php echo tr('Servizio'); ?>" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Peso lordo'); ?>", "name": "peso_lordo", "value": "$peso_lordo$", "icon-after": "KG" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Volume'); ?>", "name": "volume", "value": "$volume$", "icon-after": "M<sup>3</sup>" ]}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            {[ "type": "select", "label": "<?php echo tr('Conto predefinito di vendita'); ?>", "name": "idconto_vendita", "value": "$idconto_vendita$", "ajax-source": "conti-vendite" ]}
                        </div>

                        <div class="col-md-6">
                            {[ "type": "number", "label": "<?php echo tr('Minimo di vendita'); ?>", "name": "minimo_vendita", "value": "<?php echo ($prezzi_ivati ? $articolo->minimo_vendita_ivato : $articolo->minimo_vendita); ?>", "icon-after": "<?php echo currency(); ?>", "help": "<?php echo ($prezzi_ivati ? tr('Importo IVA inclusa') : ''); ?>" ]}
                        </div>
                    </div>
                </div>
            </div>

            <?php
                //id prezzo di acquisto standard = 99999 perchè se no da problemi
                //con la select (non mostra il campo selezionato quando id = 0)
                $queryListiniOrigine = '
                    SELECT
                    IF(id_listino_origine = 0, 99999, id_listino_origine) as id,
                    IF(mg_listini.nome is null, \'Prezzo di acquisto standard\', mg_listini.nome) as descrizione,
                    prezzo_unitario
                    FROM mg_logiche_calcolo
                    LEFT JOIN mg_listini ON mg_listini.id = mg_logiche_calcolo.id_listino_origine
                    LEFT JOIN mg_listini_articoli ON mg_listini_articoli.id_listino = mg_logiche_calcolo.id_listino_origine
                    and mg_listini_articoli.id_articolo = '.prepare($id_record).'
                    GROUP BY id_listino_origine';

                $listini_origine = $dbo->fetchArray($queryListiniOrigine);
            ?>
            <?php if ($listini_origine) { ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php echo tr('Logiche di calcolo'); ?>
                        </h3>
                    </div>

                    <div class="panel-body">
                        <div class="clearfix"></div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                    $queryLogiche = '
                                        SELECT id_listino_origine, id_listino_destinazione, mg_listini.nome as descrizione_destinazione,
                                        formula_da_applicare, prezzo_unitario, prezzo_vendita
                                        FROM mg_logiche_calcolo
                                        LEFT JOIN mg_listini ON mg_listini.id = mg_logiche_calcolo.id_listino_destinazione
                                        LEFT JOIN mg_listini_articoli ON mg_listini_articoli.id_listino = mg_logiche_calcolo.id_listino_destinazione
                                        and mg_listini_articoli.id_articolo = '.prepare($id_record).'
                                        LEFT JOIN mg_articoli on mg_articoli.id = '.prepare($id_record);

                                    $logiche = $dbo->fetchArray($queryLogiche);

                                    $prezzo_listino_origine = 0;

                                    if (count($listini_origine) == 1) {
                                        $prezzo_listino_origine = $listini_origine[0]['prezzo_unitario'];
                                    }

                                ?>

                                <div class="hidden" id="listini-origine">
                                    <?php echo json_encode($listini_origine); ?>
                                </div>
                                <div class="hidden" id="logiche">
                                    <?php echo json_encode($logiche); ?>
                                </div>
                                <div class="hidden" id="input-template">
                                    {[ "type": "number", "id": "", "name": "", "value": "", "icon-after": "<?php echo currency(); ?>"]}
                                </div>

                                <?php if (count($listini_origine) > 0) { ?>
                                    <?php if (count($listini_origine) > 1) { ?>
                                        {[ "type": "select", "label": "<?php echo tr('Listino di origine') ?>", "id": "listino_di_origine", "name": "listino_origine", "value": "", "values": "query=<?php echo $queryListiniOrigine ?>"]}
                                    <?php } else { ?>
                                        {[ "type": "hidden", "id": "listino_di_origine", "name": "listino_origine", "value": "<?php echo $listini_origine[0]['id']?>"]}
                                    <?php } ?>
                                    {[ "type": "number", "label": "<?php echo tr('Prezzo di partenza') ?>", "id": "prezzo_di_partenza", "name": "prezzo_di_partenza", "value": "<?php echo $prezzo_listino_origine ?>", "icon-after": "<?php echo currency(); ?>"]}

                                    <!-- crea tabella per logiche di calcolo -->
                                    <table id="tbl-logiche" class="table table-bordered table-condensed table-striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo tr('Listino di destinazione') ?></th>
                                                <th><?php echo tr('Operazione') ?></th>
                                                <th><?php echo tr('Valore') ?></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                                if (count($listini_origine) == 1) {
                                                    foreach ($logiche as $logica) {
                                                        $descrizione = $logica['descrizione_destinazione'];
                                                        $prezzo = $logica['prezzo_unitario'];
                                                        //if descrizione is null
                                                        if (empty($descrizione)) {
                                                            $descrizione = tr('Prezzo di vendita');
                                                            $prezzo = $logica['prezzo_vendita'];
                                                        }
                                                        if (empty($prezzo)) {
                                                            $prezzo = 0;
                                                        }
                                                        $prezzo = str_replace('.', ',', $prezzo);


                                                        echo '
                                                        <tr>
                                                            <td>'.$descrizione.'</td>
                                                            <td>'.$logica['formula_da_applicare'].'%</td>
                                                            <td>
                                                                {[ "type": "number", "id": "listino[' . $logica['id_listino_destinazione'] . ']", "name": "listino[' . $logica['id_listino_destinazione'] . ']", "value": "' . $prezzo . '", "icon-after": "' . currency() . '"]}
                                                            </td>
                                                        </tr>';
                                                    }
                                                }
                                            ?>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?php echo tr('Ultimi 20 prezzi di acquisto'); ?>
                    </h3>
                </div>

                <div class="panel-body">
                    <div class="clearfix"></div>

                    <div class="row">
                        <div class="col-md-12" id="prezziacquisto"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?php echo tr('Ultimi 20 prezzi di vendita'); ?>
                    </h3>
                </div>

                <div class="panel-body">
                    <div class="clearfix"></div>

                    <div class="row">
                        <div class="col-md-12" id="prezzivendita"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script>
let iva_vendita = $("#idiva_vendita");
let percentuale = 0;

$(document).ready(function() {
    if (iva_vendita.val()) {
        percentuale = parseFloat(iva_vendita.selectData().percentuale);
    }
    if (!percentuale) {
        percentuale =  parseFloat(input("aliquota_predefinita").get());
    }

    input("coefficiente").on('keyup', function(){
        if (input("coefficiente").get()) {
            let prezzo_vendita = input('prezzo_acquisto').get() * input("coefficiente").get();
            if (parseFloat(input("prezzi_ivati").get())) {
                prezzo_vendita = prezzo_vendita + (prezzo_vendita * percentuale / 100);
            }
            input("prezzo_vendita").set(prezzo_vendita);
            input("prezzo_vendita").disable();
            $("#scorporaIva").addClass("disabled");
        } else {
            input("prezzo_vendita").enable();
            $("#scorporaIva").removeClass("disabled");
        }
    });

    input("prezzo_acquisto").on('keyup change', function(){
        if (input("coefficiente").get()) {
            let prezzo_vendita = input('prezzo_acquisto').get() * input("coefficiente").get();
            if (parseFloat(input("prezzi_ivati").get())) {
                prezzo_vendita = prezzo_vendita + (prezzo_vendita * percentuale / 100);
            }
            input("prezzo_vendita").set(prezzo_vendita);
            input("prezzo_vendita").disable();
            $("#scorporaIva").addClass("disabled");
        } else {
            input("prezzo_vendita").enable();
            $("#scorporaIva").removeClass("disabled");
        }
    });
});

$("#categoria").change(function() {
    updateSelectOption("id_categoria", $(this).val());

	$("#subcategoria").val(null).trigger("change");
});

function scorporaIva() {
    if(!percentuale) return;

    let input = $("#prezzo_vendita");
    let prezzo = input.val().toEnglish();
    let scorporato = prezzo * 100 / (100 + percentuale);
    input.val(scorporato);
}

$("#scorporaIva").click( function() {
	scorporaIva();
});
</script>


<?php

// Collegamenti diretti
// Fatture, ddt, preventivi collegati a questo articolo
$elementi = $dbo->fetchArray('SELECT `co_documenti`.`id`, `co_documenti`.`data`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_tipidocumento`.`descrizione` AS tipo_documento, `co_tipidocumento`.`dir`, SUM(co_righe_documenti.qta) AS qta_totale, ((SUM(co_righe_documenti.prezzo_unitario)-SUM(co_righe_documenti.sconto_unitario))/SUM(co_righe_documenti.qta)) AS prezzo_unitario, SUM(co_righe_documenti.prezzo_unitario)-SUM(co_righe_documenti.sconto_unitario) AS prezzo_totale FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id`=`co_righe_documenti`.`iddocumento` WHERE `co_righe_documenti`.`idarticolo` = '.prepare($id_record).' GROUP BY co_documenti.id

UNION SELECT `dt_ddt`.`id`, `dt_ddt`.`data`, `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`, `dt_tipiddt`.`descrizione` AS tipo_documento, `dt_tipiddt`.`dir`, SUM(dt_righe_ddt.qta) AS qta_totale, ((SUM(dt_righe_ddt.prezzo_unitario)-SUM(dt_righe_ddt.sconto_unitario))/SUM(dt_righe_ddt.qta)) AS prezzo_unitario, SUM(dt_righe_ddt.prezzo_unitario)-SUM(dt_righe_ddt.sconto_unitario) AS prezzo_totale FROM `dt_ddt` JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`idddt` WHERE `dt_righe_ddt`.`idarticolo` = '.prepare($id_record).' GROUP BY dt_ddt.id

UNION SELECT `co_preventivi`.`id`, `co_preventivi`.`data_bozza`, `co_preventivi`.`numero`,  0 AS numero_esterno , "Preventivo" AS tipo_documento, 0 AS dir, SUM(co_righe_preventivi.qta) AS qta_totale, ((SUM(co_righe_preventivi.prezzo_unitario)-SUM(co_righe_preventivi.sconto_unitario))/SUM(co_righe_preventivi.qta)) AS prezzo_unitario, SUM(co_righe_preventivi.prezzo_unitario)-SUM(co_righe_preventivi.sconto_unitario) AS prezzo_totale FROM `co_preventivi` INNER JOIN `co_righe_preventivi` ON `co_preventivi`.`id`=`co_righe_preventivi`.`idpreventivo` WHERE `co_righe_preventivi`.`idarticolo` = '.prepare($id_record).' GROUP BY co_preventivi.id ORDER BY `data`');

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-striped table-bordered table-extra-condensed">
            <tr>
                <th>'.tr('Documento').'</td>
                <th width="12%" class="text-center">'.tr('Quantità').'</td>
                <th width="15%" class="text-center">'.tr('Prezzo unitario').'</td>
                <th width="15%" class="text-center">'.tr('Prezzo totale').'</td>
            <tr>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        //se non è un preventivo è un ddt o una fattura
        //se non è un ddt è una fattura.
        if (in_array($elemento['tipo_documento'], ['Preventivo'])) {
            $modulo = 'Preventivi';
        } elseif (!in_array($elemento['tipo_documento'], ['Ddt di vendita', 'Ddt di acquisto', 'Ddt in entrata', 'Ddt in uscita'])) {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto';
        } else {
            $modulo = ($elemento['dir'] == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
        }

        $id = $elemento['id'];

        echo '
            <tr>
                <td>'.Modules::link($modulo, $id, $descrizione).'</td>
                <td class="text-center">'.Translator::numberToLocale($elemento['qta_totale']).'</td>
                <td class="text-right">'.moneyFormat($elemento['prezzo_unitario']).'</td>
                <td class="text-right">'.moneyFormat($elemento['prezzo_totale']).'</td>
            <tr>';
    }

    echo '
        </table>
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-error">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
input('id_fornitore').change(function(){
    let prezzo_unitario = $(this).selectData() ? $(this).selectData().prezzo_unitario  : "";
    if(input('id_fornitore').get()){
        input('prezzo_acquisto').set(prezzo_unitario);
        input('prezzo_acquisto').disable();
    } else {
        input('prezzo_acquisto').enable();
        input('prezzo_acquisto').set('0');
    }
});
$(document).ready(function(){
    if(input('id_fornitore').get()){
        input('prezzo_acquisto').disable();
    }

    $("#prezziacquisto").load("<?php echo base_path(); ?>/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo="+ <?php echo $id_record; ?> + "&limit=20");

    $("#prezzivendita").load("<?php echo base_path(); ?>/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo="+ <?php echo $id_record; ?> + "&limit=20");


    //LOGICHE DI CALCOLO
    $('#edit-form').on('change', '#listino_di_origine', function(){
        //get value
        var id_listino_di_origine = $(this).val();

        var tbody = $('#tbl-logiche tbody');
        tbody.html('');

        var logiche = JSON.parse($('#logiche').html());
        var listiniOrigine = JSON.parse($('#listini-origine').html());

        $.each(listiniOrigine, function (i, listinoOrigine) {
            if (listinoOrigine.id == id_listino_di_origine) {
                if (listinoOrigine.prezzo_unitario == null) {
                    if (id_listino_di_origine == '99999') { //caso prezzo di acquisto standard
                        listinoOrigine.prezzo_unitario = $('input[name="prezzo_acquisto"]').val();
                        listinoOrigine.prezzo_unitario = listinoOrigine.prezzo_unitario.replace(/\./g, '');
                        listinoOrigine.prezzo_unitario = listinoOrigine.prezzo_unitario.replace(',', '.');
                    } else {
                        listinoOrigine.prezzo_unitario = 0;
                    }
                }

                $('#prezzo_di_partenza').val(parseFloat(listinoOrigine.prezzo_unitario).toFixed(4));
            }
        });

        var $inputTemplate = $('#input-template');

        if (id_listino_di_origine == '99999') { //caso prezzo di acquisto standard
            id_listino_di_origine = 0;
        }

        console.log('3-id_listino_di_origine: ' + id_listino_di_origine);
        $.each(logiche, function (i, logica) {
            if (logica.id_listino_origine == id_listino_di_origine) {
                descrizione_destinazione = logica.descrizione_destinazione;
                prezzo = logica.prezzo_unitario;

                if (descrizione_destinazione == null) {
                    descrizione_destinazione= 'Prezzo di vendita';
                    prezzo = logica.prezzo_vendita;
                }

                if (prezzo == null) {
                    /*if (id_listino_di_origine == 0) { //caso prezzo di acquisto standard
                        prezzo = parseFloat($('#prezzo_di_partenza').val()) + (parseFloat($('#prezzo_di_partenza').val()) * parseFloat(logica.formula_da_applicare) / 100);
                    } else {
                        prezzo = 0;
                    }*/
                    prezzo = 0;

                }

                $inputTemplate.find('input').attr('name', 'listino[' + logica.id_listino_destinazione + ']');
                $inputTemplate.find('input').attr('id', 'listino[' + logica.id_listino_destinazione + ']');
                $inputTemplate.find('input').attr('value', parseFloat(prezzo).toFixed(4));

                tbody.append(
                    '<tr>' +
                        '<td>' + descrizione_destinazione + '</td>' +
                        '<td>' + logica.formula_da_applicare + '%</td>' +
                        '<td>' +
                            $inputTemplate.html() +
                        '</td>' +
                    '</tr>'
                );
            }
        });
    })

    $('#edit-form').on('keyup', '#prezzo_di_partenza', function(){
        var prezzo_di_partenza = $(this).val();
        prezzo_di_partenza = prezzo_di_partenza.replace(/\./g, '');
        prezzo_di_partenza = prezzo_di_partenza.replace(',', '.');

        var tbody = $('#tbl-logiche tbody');

        //foreach tr
        tbody.find('tr').each(function (i) {
            if (isNaN(prezzo_di_partenza)) {
                $(this).find('td').eq(2).text('0 €');
            } else {
                var calcolo = $(this).find('td').eq(1);
                calcolo = parseFloat(calcolo.text().replace('%', ''));

                var prezzo = prezzo_di_partenza * (1 + (calcolo / 100));

                $(this).find('td').eq(2).find('input').val(prezzo.toFixed(4));
            }
        });
    });
    //FINE LOGICHE DI CALCOLO
});
</script>
