<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$id_ordine = get('idordine');
$id_fornitore = get('idfornitore');
$tipo_fornitore = get('fornitore');

$module_ordini = Modules::get('Ordini cliente');
$plugin_distinte = $dbo->fetchNum("SELECT * FROM zz_plugins WHERE name='Distinta base'");
$fornitori = getFornitori();
$articoli_da_ordinare = getArticoliDaOrdinare();
$fornitori_articoli = getFornitoriArticoli($articoli_da_ordinare);
?>

<form action="" method="post" id="form_crea_ordine">
    <input type="hidden" name="op" value="crea_ordine">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-3 col-sm-6">
            <input type="text" class="form-control" id="search-article" placeholder="<?php echo tr('Cerca articolo')?>" />
        </div>
        <div class="col-md-3 col-sm-6">
            <select class="superselect openstamanager-input select-input" id="search-fornitore">
                <option value="0"><?php echo tr('Tutti i fornitori')?></option>
                <?php foreach ($fornitori as $fornitore) { ?>
                    <option value="<?php echo $fornitore['id']?>">
                        <?php echo $fornitore['descrizione']?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>


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
                    <td class="descrizione"><?php echo $articolo['descrizione'] ?></td>
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
                    <td class="fornitori">
                        <div>
                            <select class="superselect openstamanager-input select-input" name="<?php echo 'idanagrafica['.$articolo['idarticolo'].']'?>">
                                <option value="0"><?php echo tr('') ?></option>
                                <?php foreach ($fornitori_articoli[$articolo['idarticolo']] as $fornitore_articolo) { ?>
                                    <option value="<?php echo $fornitore_articolo['id'] ?>">
                                        <?php echo $fornitore_articolo['descrizione'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <!--{[ "type": "select", "class": "", "label": "", "name": "<?php echo 'idanagrafica['.$articolo['idarticolo'].']'?>", "values": "query=<?php echo $query;?>", "value": "<?php echo $id_fornitore ?? 0 ?>" ]}-->
                    </td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot class="hide">
            <tr>
                <td colspan="11" style="text-align: center;">
                    <span>
                        <?php echo tr('Nessun articolo trovato'); ?>
                    </span>
                </td>
            </tr>
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

        $('#search-article').keyup(function(){
            var value = $(this).val().toLowerCase();
            console.log(value);
            $("#table tbody tr").filter(function() {
                //search only in the coloumn with class description
                $(this).toggle($(this).find(".descrizione").text().toLowerCase().indexOf(value) > -1)
            });

            //count visible rows
            var count = $("#table tbody tr:visible").length;
            if (count == 0) {
                $("#table tfoot").removeClass("hide");
            } else {
                $("#table tfoot").addClass("hide");
            }
        });

        $('#search-fornitore').change(function(){
            var value = $(this).val().toLowerCase();

            $("#table tbody tr").filter(function() {
                options = $(this).find('.fornitori option');

                var found = false;
                var i = 0

                while(i < options.length && found == false){
                    if(options[i].value == value){
                        found = true;
                    }
                    i++;
                }

                $(this).toggle(found);
            });

            //count visible rows
            var count = $("#table tbody tr:visible").length;
            if (count == 0) {
                $("#table tfoot").removeClass("hide");
            } else {
                $("#table tfoot").addClass("hide");
            }
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
                    data = JSON.parse(data);
                    var content = 'Nessuna giacenza disponibile';

                    if (data.length > 0) {
                        content = 'Giacenze \n \n';
                        $.each(data, function(key, value) {
                            content += value.descrizione + ':  ' + value.giacenza + ' ' + value.um + '\n';
                        });
                    }

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
                    swalStep2();
                }).catch(function (result) {
                    $("#inBozza").val(0);
                    swalStep2();
                });
            }
        } else {
            swal("'.tr('Errore').'", "'.tr('Nessun articolo selezionato!').'", "error");
        }
    }

    function swalStep2() {
        swal({
            title: "'.tr("Creazione ordine fornitore").'",
            html: "'.tr("Desideri procedere alla creazione dell'Ordine fornitore per questi articoli?").'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Procedi').'"
        }).then(function (result) {
            $("#form_crea_ordine").submit();
        });
    }

</script>';
