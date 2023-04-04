<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$id_ordine = get('idordine');
$id_fornitore = get('idfornitore');
$tipo_fornitore = get('fornitore');

$module_ordini = Modules::get('Ordini cliente');
$plugin_distinte = $dbo->fetchNum("SELECT * FROM zz_plugins WHERE name='Distinta base'");
$articoli_da_ordinare = getArticoliDaOrdinare();
?>

<form action="" method="post" id="form_crea_ordine">
    <input type="hidden" name="op" value="crea_ordine">
    <input type="hidden" name="backto" value="record-edit">

    <!-- Articoli da ordinare -->
    <table class="table table-condensed table-striped table-bordered" id="table" style="border-left: 10px solid #297fcc;">
        <thead>
            <tr>
                <th><input type="checkbox" class="selectall"></th>
                <th style="width:auto"><?php echo tr('Numero ordine')?></th>
                <th style="width:25%"><?php echo tr('Articolo')?></th>
                <th><?php echo tr('Magazzino')?></th>
                <th><?php echo tr('Minimo sede')?></th>
                <th><?php echo tr('Q.tà disponibile sede')?></th>
                <th><?php echo tr('Q.tà disponibile totale')?></th>
                <th><?php echo tr('Q.tà da consegnare')?></th>
                <th><?php echo tr('Q.tà ordinata')?></th>
                <th style="width:7%"><?php echo tr('Q.tà mancante')?></th>
                <th style="width:15%"><?php echo tr('Fornitore') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articoli_da_ordinare as $articolo) { ?>
                <tr data-id="<?php echo $articolo['idarticolo']?>" data-sede="<?php echo $articolo['id_sede_partenza']?>">
                    <td>
                        <input type="checkbox" class="check" name="<?php echo 'ordinare['.$articolo['idarticolo'].']'?>"
                            id="<?php echo 'checkbox_'.$articolo['id']?>">
                    </td>
                    <td>
                        <a href="<?php echo base_path().'/editor.php?id_module='.$module_ordini['id'].'&id_record='.$articolo['id']?>" target="_blank">
                            <?php echo $articolo['numero']?>
                        </a>
                    </td>
                    <td><?php echo $articolo['descrizione'] ?></td>
                    <td><?php echo $articolo['Magazzino'] ?></td>
                    <td><?php echo Translator::numberToLocale($articolo['minimo_sede']).' '.$articolo['um']?></td>
                    <td><?php echo Translator::numberToLocale($articolo['disponibilita_sede']).' '.$articolo['um']?></td>
                    <td>
                        <?php echo Translator::numberToLocale($articolo['disponibilita_totale']).' '.$articolo['um']?>
                        <small>
                            <span class="pull-right tooltip-disp" title="">
                                <i class="fa fa-question-circle-o"></i>
                            </span>
                        </small>
                    </td>
                    <td><?php echo Translator::numberToLocale($articolo['qta_non_consegnata_cliente']).' '.$articolo['um']?></td>
                    <td><?php echo Translator::numberToLocale($articolo['qta_ordinata_fornitore']).' '.$articolo['um']?></td>
                    <td>
                        <input type="number" name="<?php echo 'qta_ordinare['.$articolo['idarticolo'].']'?>" class="form-control text-center"
                            value="<?php echo number_format($articolo['qta_mancante'], 2) ?>">
                    </td>
                    <td>
                        <?php
                            $query = '
                                SELECT idanagrafica as id, ragione_sociale as descrizione
                                FROM an_anagrafiche ana
                                WHERE ana.idanagrafica = 1
                                UNION
                                SELECT idanagrafica as id, ragione_sociale as descrizione
                                FROM mg_fornitore_articolo fa
                                INNER JOIN an_anagrafiche a ON fa.id_fornitore = a.idanagrafica
                                WHERE id_articolo='.$articolo['idarticolo'];

                            $results = $dbo->fetchArray($query);
                        ?>
                        <select class="superselect openstamanager-input select-input">
                            <option value=""><?php echo tr('') ?></option>
                            <?php foreach ($results as $result) { ?>
                                <option value="<?php echo $result['id'] ?>">
                                    <?php echo $result['descrizione'] ?>
                                </option>
                            <?php } ?>
                        </select>

                        <!--{[ "type": "select", "class": "", "label": "", "name": "<?php echo 'idanagrafica['.$articolo['idarticolo'].']'?>", "values": "query=<?php echo $query;?>", "value": "<?php echo $id_fornitore ?? 0 ?>" ]}-->
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <a type="button" class="btn btn-primary btn-lg" id="button_create" onclick="crea_ordine();">
        <i class="fa fa-plus"></i><?php echo tr('Crea ordine fornitore') ?>
    </a>

    <input type="hidden" id="inBozza" name="inBozza" value="0">
</form>

<style>
    #button_create {
        position:fixed;
        bottom:10px;
        width:300px;
        left:50%;
        margin-right:35px;
    }
</style>


<script>
    $(document).ready(function() {
         $(".selectall").click(function(){
            var checked = $(this).is(":checked");

            $(this).closest("table").find(".check").each(function(){
                $(this).prop("checked", checked);
            });
        });

        $(".check").change(function() {
            var checked = this.checked;
            $(this).closest("tr").find("select").each(function(){
                $(this).prop("required", checked);
            });
        });

        $("#idfornitore").change(function(){
            var id_fornitore = $(this).val() != null ? $(this).val() : "";
            location.href = globals.rootdir + "/controller.php?id_module=" + globals.id_module + "&idfornitore=" + id_fornitore;
        });

        $(".tooltip-disp").mouseenter(function(){
            var $row = $(this).closest("tr");
            var id_articolo = $row.data("id");
            var id_sede = $row.data("sede");
            console.log(id_sede);
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                data: {
                    id_module: globals.id_module,
                    op: "get_disponibilita_magazzini",
                    id_articolo: id_articolo,
                    id_sede: id_sede
                },
                success: function(data) {
                    console.log(data);

                    data = JSON.parse(data);

                    //foreache data
                    var content = 'Giacenze \n \n';

                    if (data.length == 0) {
                        content = 'Nessuna giacenza disponibile';
                    } else {
                        $.each(data, function(key, value) {
                            content += value.descrizione + ':  ' + value.giacenza + ' ' + value.um + '\n';
                        });
                    }

                    //add content in .tip
                    $row.find(".tooltip-disp").attr("title", content);

                }
            });
        });

    });
</script>

<?php

echo '
<script>
    function crea_ordine(){
        if($(".check:checked").length != 0) {
            if($("#form_crea_ordine").parsley().validate()) {
                swal({
                    title: "'.tr("Creazione ordine fornitore").'",
                    html: "'.tr("Desideri inserire gli articoli in eventuali ordini aperti?").'",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonText: "'.tr('Si').'",
                    cancelButtonText: "'.tr('No').'"
                }).then(function (result) {
                    $("#inBozza").val(1);
                    swal({
                        title: "'.tr("Creazione ordine fornitore").'",
                        html: "'.tr("Desideri procedere alla creazione dell'Ordine fornitore per questi articoli?").'",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonText: "'.tr('Procedi').'"
                    }).then(function (result) {
                        $("#form_crea_ordine").submit();
                    });
                });
            }
        } else {
            swal("'.tr('Errore').'", "'.tr('Nessun articolo selezionato!').'", "error");
        }
    }
</script>';
