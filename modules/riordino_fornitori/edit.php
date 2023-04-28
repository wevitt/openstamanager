<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$id_ordine = get('idordine');
$id_fornitore = get('idfornitore');
$tipo_fornitore = get('fornitore');

$module_ordini = Modules::get('Ordini cliente');
$module_articoli = Modules::get('Articoli');

$plugin_distinte = $dbo->fetchNum("SELECT * FROM zz_plugins WHERE name='Distinta base'");
$articoli_da_ordinare = getArticoliDaOrdinare();
$fornitori_articoli = getFornitoriArticoli($articoli_da_ordinare);

$magazzini = getSedi();
$json_magazzini = json_encode($magazzini);
$fornitori = getFornitori();
$json_fornitori = json_encode($fornitori);
?>

<form action="" method="post" id="form_crea_ordine">
    <input type="hidden" name="op" value="crea_ordine">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-4 col-sm-6">
            <input type="text" class="form-control" id="search-article" placeholder="<?php echo tr('Cerca articolo')?>" />
        </div>
        <div class="col-md-4 col-sm-6">
            <select class="superselect openstamanager-input select-input" id="search-fornitore">
                <option value=""><?php echo tr('Tutti i fornitori')?></option>
                <?php foreach ($fornitori as $fornitore) { ?>
                    <option value="<?php echo $fornitore['id']?>">
                        <?php echo $fornitore['descrizione']?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="col-md-4 col-sm-6">
            <select class="superselect openstamanager-input select-input" id="search-magazzino">
                <option value=""><?php echo tr('Tutti i magazzini')?></option>
                <?php foreach ($magazzini as $magazzino) { ?>
                    <option value="<?php echo $magazzino['id']?>">
                        <?php echo $magazzino['nomesede']?>
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
                <th style="width:15%"><?php echo tr('Seleziona fornitore') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articoli_da_ordinare as $i => $articolo) { ?>
                <tr data-id="<?php echo $articolo['idarticolo']?>" data-sede="<?php echo $articolo['id_sede_partenza']?>">
                    <td>
                        <input
                            type="checkbox" class="check"
                            name="ordinare[<?php echo $articolo['numero'].'_'.$articolo['idarticolo']?>]"
                            id="<?php echo 'checkbox_'.$articolo['id']?>"
                        />
                    </td>
                    <td>
                        <a href="<?php echo base_path().'/editor.php?id_module='.$module_ordini['id'].'&id_record='.$articolo['id']?>" target="_blank">
                            <?php echo $articolo['numero']?>
                        </a>
                    </td>
                    <td class="descrizione">
                        <a href="<?php echo base_path().'/editor.php?id_module='.$module_articoli['id'].'&id_record='.$articolo['idarticolo']?>" target="_blank">
                            <?php echo $articolo['descrizione'] ?></td>
                        </a>
                    <td class="magazzino" data-id-sede=<?php echo $articolo['id_sede_partenza'] ?>>
                        <input
                            type="hidden"
                            name="sede_partenza[<?php echo $articolo['numero'].'_'.$articolo['idarticolo'] ?>]"
                            value="<?php echo $articolo['id_sede_partenza'] ?>"
                        />
                        <?php echo $articolo['Magazzino'] ?>
                    </td>
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
                        <input
                            type="number" class="form-control text-center"
                            name="qta_ordinare[<?php echo $articolo['numero'].'_'.$articolo['idarticolo'] ?>]"
                            value="<?php echo number_format($articolo['qta_mancante'], 2) ?>">
                    </td>
                    <td class="fornitori">
                        <?php
                            //Se nel magazzino centrale si riesce a coprire la qta mancante allora aggiungo
                            //btn spostamento tra depositi

                            $spostamento_tra_depositi = false;
                            if ($articolo['id_sede_partenza'] > 0) {
                                if ($articolo['disponibilita_totale'] > $articolo['disponibilita_sede']) {
                                    $spostamento_tra_depositi = true;
                                }
                            }
                        ?>
                        <div style="display: flex">
                            <div style="width:75%; padding-right:5%;">
                                <select
                                    class="superselect openstamanager-input select-input"
                                    id="select_<?php echo $i;?>"
                                    name="idanagrafica[<?php echo $articolo['numero'].'_'.$articolo['idarticolo'] ?>]"
                                >
                                    <option value=""><?php echo tr('') ?></option>
                                    <?php foreach ($fornitori_articoli[$articolo['idarticolo']] as $fornitore_articolo) { ?>
                                        <option value="<?php echo $fornitore_articolo['id'] ?>">
                                            <?php echo $fornitore_articolo['descrizione'] ?>
                                        </option>
                                        <?php
                                            if ($fornitore_articolo['id'] == end($fornitori_articoli[$articolo['idarticolo']])['id']) {
                                                echo '<option value="tutti">'.tr('Mostra tutti i fornitori attivi').'</option>';
                                            }
                                        ?>
                                    <?php } ?>
                                </select>
                            </div>
                            <div style="width:20%">
                                <span class="input-group-addon after no-padding">
                                    <button type="button" class="btn" data-toggle="tooltip" title="Spostamento tra depositi" onclick="crea_spostamento($(this));"
                                        <?php echo (!$spostamento_tra_depositi) ? 'disabled' : ''; ?> >
                                        <i class="fa fa-truck"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
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
    <input type="hidden" id="spostamentoInterno" name="spostamentoInterno" value="0">
    <input type="hidden" id="magazzinoScelto" name="magazzinoScelto" value="">
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
        filter();
        tooltip();
        select();

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
    });

    function crea_spostamento($this) {
        if (!$this.attr('disabled')) {
            swal({
                title: "<?php echo tr("Creazione spostamento interno");?>",
                html:
                    '<div class="swal-checkbox-container">' +
                        '<label for="check-bozza"><?php echo tr("Aggiungere agli ordini di acquisto non ancora emessi?");?></label>' +
                        '<div class="form-group checkbox-group">' +
                            '<input type="hidden" name="" value="" class="openstamanager-input">' +
                            '<input type="checkbox" id="check-bozza" value="" class="hidden" type="checkbox" data-parsley-errors-container="#check-bozza71-errors" onchange="$(this).parent().find(\'[type = hidden]\').val(+this.checked).trigger(\'change\')"/>' +
                            '<div class="btn-group checkbox-buttons">' +
                                '<label for="check-bozza" class="btn btn-default">' +
                                    '<span class="fa fa-check text-success"></span>' +
                                    '<span class="fa fa-close text-danger"></span>' +
                                '</label>' +
                                '<label for="check-bozza" class="btn btn-default active">' +
                                    '<span class="text-success">Attivato</span>' +
                                    '<span class="text-danger">Disattivato</span>' +
                                '</label>' +
                            '</div>' +
                            '<div id="check-bozza71-errors"></div>' +
                        '</div>' +
                    '</div>',
                type: "warning",
                onOpen: function (el) {
                    $('#magazzino-unico').select2({
                        placeholder: "<?php echo tr('Seleziona un magazzino')?>",
                        allowClear: true
                    });
                },
                showCancelButton: true,
                confirmButtonText: "<?php echo tr('Si')?>",
                cancelButtonText: "<?php echo tr('No');?>"
            }).then(function (result) {
                $this.closest("tr").find(".check").prop("checked", true);
                $('#spostamentoInterno').val($this.closest('tr').data('sede'));
                $('#inBozza').val(($('#check-bozza').is(":checked")) ? '1' : '0');
                $('#magazzinoScelto').val($('#magazzino-unico').val() != null ? $('#magazzino-unico').val() : '');
                $("#form_crea_ordine").submit();
            });
        }
    }

    function crea_ordine() {
        if ($(".check:checked").length != 0) {
            //controlla che la sede sia uguale per tutti gli articoli
            var flag = true;
            $(".check:checked").each(function(i, e) {
                if (i == 0) {
                    current_sede = $(this).closest("tr").find(".magazzino").data("id-sede");
                } else {
                    if (current_sede != $(this).closest("tr").find(".magazzino").data("id-sede")) {
                        flag = false;
                    }
                }
            });

            var extra_html = "";
            if (!flag) {
                var magazzini = <?php echo $json_magazzini; ?>;
                extra_html =
                    '<label for="magazzino-unico"><?php echo tr("Attenzione, sono stati selezionati articoli mancanti in magazzini diversi, vuoi inviare ad uno stesso magazzino?");?></label>' +
                    '<select class="select-input" id="magazzino-unico" style="width:100%">' +
                        '<option value=""><?php echo tr('Tutti i fornitori')?></option>';

                $.each(magazzini, function(key, value) {
                    extra_html += '<option value="' + value.id + '">' + value.nomesede + '</option>';
                });

                extra_html += '</select>';
            } else {
                extra_html = "";
            }

            if ($("#form_crea_ordine").parsley().validate()) {
                swal({
                    title: "<?php echo tr("Creazione ordine fornitore");?>",
                    html:
                        '<div class="swal-checkbox-container">' +
                            '<label for="check-bozza"><?php echo tr("Aggiungere agli ordini di acquisto non ancora emessi?");?></label>' +
                            '<div class="form-group checkbox-group">' +
                                '<input type="hidden" name="" value="" class="openstamanager-input">' +
                                '<input type="checkbox" id="check-bozza" value="" class="hidden" type="checkbox" data-parsley-errors-container="#check-bozza71-errors" onchange="$(this).parent().find(\'[type = hidden]\').val(+this.checked).trigger(\'change\')"/>' +
                                '<div class="btn-group checkbox-buttons">' +
                                    '<label for="check-bozza" class="btn btn-default">' +
                                        '<span class="fa fa-check text-success"></span>' +
                                        '<span class="fa fa-close text-danger"></span>' +
                                    '</label>' +
                                    '<label for="check-bozza" class="btn btn-default active">' +
                                        '<span class="text-success">Attivato</span>' +
                                        '<span class="text-danger">Disattivato</span>' +
                                    '</label>' +
                                '</div>' +
                                '<div id="check-bozza71-errors"></div>' +
                            '</div>' +
                            extra_html +
                        '</div>',
                    type: "warning",
                    onOpen: function (el) {
                        $('#magazzino-unico').select2({
                            placeholder: "<?php echo tr('Seleziona un magazzino')?>",
                            allowClear: true
                        });
                    },
                    showCancelButton: true,
                    confirmButtonText: "<?php echo tr('Si')?>",
                    cancelButtonText: "<?php echo tr('No');?>"
                }).then(function (result) {
                    $('#inBozza').val(($('#check-bozza').is(":checked")) ? '1' : '0');
                    $('#magazzinoScelto').val($('#magazzino-unico').val() != null ? $('#magazzino-unico').val() : '');
                    $("#form_crea_ordine").submit();
                });
            }
        } else {
            swal("<?php echo tr('Errore');?>", "<?php echo tr('Nessun articolo selezionato!');?>", "error");
        }
    }

    function filter() {
        //nome articolo
        $('#search-article').keyup(function(){
            var value = $(this).val().toLowerCase();
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

        //magazzino
        $('#search-magazzino').change(function(){
            var value = $(this).val().toLowerCase();

            if (value == '') {
                $("#table tbody tr").show();
            } else {
                $("#table tbody tr").filter(function() {
                    $(this).toggle($(this).find('.magazzino').data('id-sede') == value);
                });

                //count visible rows
                var count = $("#table tbody tr:visible").length;
                if (count == 0) {
                    $("#table tfoot").removeClass("hide");
                } else {
                    $("#table tfoot").addClass("hide");
                }
            }
        });

        //fornitore
        $('#search-fornitore').change(function(){
            var value = $(this).val().toLowerCase();

            $("#table tbody tr").filter(function() {
                options = $(this).find('.fornitori option');

                var found = false;
                var i = 0

                while(i < options.length && found == false){
                    if(options[i].value == value){
                        found = true;
                        $(this).find('.fornitori select').val(value).change();
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
    }

    function tooltip() {
        $(".tooltip-disp").mouseenter(function(){
            var $row = $(this).closest("tr");
            var id_articolo = $row.data("id");
            var id_sede = $row.data("sede");

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
                        content = 'Giacenze per sede: <br><br>';
                        $.each(data, function(key, value) {
                            content += value.descrizione + ':  ' + value.giacenza + ' ' + value.um + '<br>';
                        });
                    }

                    var $icon = $row.find(".tooltip-disp");

                    $icon.tooltipster({
                        content: content,
                        contentAsHTML: true,
                        trigger: "click",
                        interactive: true,
                        touchDevices: true,
                    });
                }
            });
        });
    }

    function select() {
        //rimozione selezione
        $('body').on('click', '.select2-selection__clear', function() {
            var id = $(this).closest(".select2-container").prev().attr("id");
            $('#' + id).val('').change();
        });

        //selziona tutti i fornitori
        $('body').on('change', '.fornitori select', function(e){
            var $row = $(this).closest("tr");
            var id_articolo = $row.data("id");
            var $select = $(this);

            if ($(this).val() == 'tutti') {
                e.preventDefault();

                var fornitori = <?php echo $json_fornitori; ?>;
                $select.find('option').remove();
                $select.append('<option value=""></option>');
                $.each(fornitori, function(key, value) {
                    $select.append('<option value="' + value.id + '">' + value.descrizione + '</option>');
                });


                //NON FUNZIONA

                /*var id = $(this).attr('data-select2-id');
                //find select2-container--bootstrap
                var $container = $(this).closest('div').find('.select2-container--bootstrap');
                //show in console log container class


                //change select2-container--focus in select2-container--open
                $container.removeClass('select2-container--focus');
                $container.addClass('select2-container--open');


                //find select2-selection
                var $selection = $container.find('.select2-selection');
                //change aria-expanded="false" in aria-expanded="true"
                $selection.attr('aria-expanded', 'true');
                // add aria-owns
                $selection.attr('aria-owns', 'select2-select_' + id + '-results');*/
            }
        });
    }
</script>
