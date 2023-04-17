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

include_once __DIR__.'/../../../core.php';

$id_modulo_fatture = Modules::get('Fatture di vendita')['id'];

?>
<input type="hidden" id="stato" value="<?php echo $record['stato']; ?>">

<div class="row" style="margin-bottom:10px;">
    <div class="col-md-6" style="display:flex; align-items:center;">
        <div style="width:100%">
            {[ "type": "number", "label": "<?php echo tr('Anticipo sull\'ordine'); ?>", "name": "anticipo", "required":"0", "value": "0", "help": "<?php echo tr('<span>Anticipo sull\'ordine</span>'); ?>", "icon-after": "<?php echo currency(); ?>", "class": "unblockable"]}
        </div>
    </div>

    <?php if ($record['stato'] == 'Accettato') { ?>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary salva-anticipo" style="margin-top:24px;">
                <?= tr('Aggiungi') ?>
            </button>
        </div>
    <?php } ?>
</div>

<?php
    $ac_acconti = $dbo->fetchArray('SELECT * FROM ac_acconti WHERE idordine='.prepare($id_record));
?>

<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
            <th width="10%"><?php echo tr('Id'); ?></th>
            <th width="40%"><?php echo tr('Descrizione'); ?></th>
            <th width="40%"><?php echo tr('Importo'); ?></th>
            <th width="10%"><?php echo tr('Azioni'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
            $totale = 0;
            $flag = false;
         ?>
        <?php foreach ($ac_acconti as $riga) { ?>
            <?php
                $acconto_righe = $dbo->fetchArray(
                    'SELECT *
                    FROM ac_acconti_righe
                    WHERE idacconto = '.prepare($riga['id'])
                );
            ?>
            <tr style="background-color: #e9ecef;">
                <td class="id-acconto"><?= $riga['id'] ?></td>
                <td><?= tr('Acconto versato') ?></td>
                <td class="importo"><?= moneyFormat($riga['importo'], 2) ?></td>
                <td class="text-center">
                    <?php if (empty($acconto_righe)) { ?>
                        <?php if ($record['stato'] != 'Bozza') { ?>
                            <a class="btn btn-sm btn-success tip" title="<?php echo tr('Crea fattura anticipo'); ?>" onclick="creaFatturaAnticipo(this)">
                                <i class="fa fa-plus"></i>
                            </a>
                        <?php } else { ?>
                            <a class="btn btn-sm btn-danger tip elimina-anticipo" title="<?php echo tr('Elimina'); ?>">
                                <i class="fa fa-trash"></i>
                            </a>
                        <?php } ?>
                    <?php } else { ?>
                        <a class="btn btn-sm btn-warning tip" title="<?php echo tr('Vai a fatture anticipo') ?>" target= "_blank" href="/controller.php?id_module=<?php echo $id_modulo_fatture; ?>&id_record=<?php echo $acconto_righe[0]['idfattura']; ?>">
                            <i class="fa fa-chevron-left"></i>
                        </a>
                    <?php } ?>
                </td>
            </tr>
            <?php foreach ($acconto_righe as $acconto_riga) { ?>
                <?php $totale = $totale + $acconto_riga['importo_fatturato'] ?>
                <tr>
                    <td><i style="margin-left:15px;" class="fa fa-arrow-right"></i></td>
                    <td><?= $acconto_riga['tipologia'] ?></td>
                    <td colspan="2"><?= moneyFormat($acconto_riga['importo_fatturato'], 2) ?></td>
                </tr>
            <?php } ?>
            <?php
                if (!empty($acconto_righe)) {
                    $flag = true;
                }
            ?>
        <?php } ?>
        <?php if ($flag) { ?>
            <tr>
                <td colspan="2" class="text-right"><?= tr('Totale residuo:') ?></td>
                <td colspan="2"><?= moneyFormat($totale, 2) ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<script>
    $(document).ready(function() {
        var timer = setInterval(function() {
            if ($("#stato").val() != 'Accettato') {
                $("#anticipo").prop("readonly", true);
            }
            clearInterval(timer);
        }, 100);


        $('body').on('click', '.salva-anticipo', function() {
            salvaAnticipo();
        });

        $('body').on('click', '.elimina-anticipo', function() {
            var id_anticipo = $(this).closest("tr").find(".id-acconto").text();
            var $row = $(this).closest("tr");

            eliminaAnticipo(id_anticipo, $row);
        });
    });

    async function salvaAnticipo() {
        anticipo = $("#anticipo").val();

        $.ajax({
            url: globals.rootdir + "/modules/ordini/actions.php",
            type: "post",
            data: {
                op: "add-anticipo",
                id_record: globals.id_record,
                anticipo: anticipo,
            },
            success: function(data){
                location.reload();
            },
        });
    }

    async function eliminaAnticipo(id_anticipo, $row) {
        swal({
            title: "Attezione",
            text: "Sei sicuro di voler eliminare questo acconto?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Sì",
            cancelButtonText: "No",
        }).then(function () {
            $.ajax({
                url: globals.rootdir + "/modules/ordini/actions.php",
                type: "post",
                data: {
                    op: "delete-anticipo",
                    id_anticipo: id_anticipo,
                },
                success: function(data){
                    //remove row
                    $row.remove();
                },
            });
        });
    }
</script>
