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

$ac_acconti = $dbo->fetchOne('SELECT * FROM ac_acconti WHERE idordine='.prepare($id_record));
if (!empty($ac_acconti)) {
    //select ac_acconti_righe
    $ac_acconti_righe = $dbo->fetchArray('SELECT * FROM ac_acconti_righe WHERE idacconto='.prepare($ac_acconti['id']));
} else {
    $ac_acconti_righe = [];
}
?>

<div class="hide" id="root-dir"><?= $rootdir ?></div>
<div class="hide" id="fattura-anticipo-dir"><?= $structure->fileurl('fattura_anticipo.php') ?></div>

<div class="row">
    <div class="col-md-6" style="display:flex; align-items:center;">
        <div style="width:100%">
            {[ "type": "number", "label": "<?php echo tr('Anticipo sull\'ordine'); ?>", "name": "anticipo", "required":0, "readonly": "<?php echo (count($ac_acconti_righe) == 0) ? 0 : 1; ?>", "value": "<?php echo $ac_acconti['importo']; ?>", "help": "<?php echo tr('<span>Anticipo sull\'ordine</span>'); ?>", "icon-after": "<?php echo currency(); ?>" ]}
        </div>
        <?php if ($record['stato'] == 'Accettato' ) { ?>
            <?php if (count($ac_acconti_righe) == 0) { ?>
                <button type="button" class="btn btn-sm btn-info tip" style="margin-top:8px; margin-left:5px" title="<?php echo tr('Crea fattura anticipo'); ?>" onclick="creaFatturaAnticipo(this)">
                    <i class="fa fa-plus"></i><?php echo tr('Crea fattura anticipo'); ?>
                </button>
            <?php } else { ?>
                <?php
                    $fattura_acconto = $dbo->fetchOne(
                        'SELECT crd.iddocumento
                        FROM co_righe_documenti crd
                        LEFT JOIN ac_acconti_righe aar ON crd.iddocumento = aar.idfattura
                        LEFT JOIN ac_acconti aa ON aa.id = aar.idacconto
                        WHERE aa.idordine ='.prepare($id_record)
                    );
                ?>
                <a class="btn btn-sm btn-info tip" target= "_blank" style="margin-top:8px; margin-left:5px" href="/controller.php?id_module=<?php echo $id_modulo_fatture; ?>&id_record=<?php echo $fattura_acconto['iddocumento']; ?>">
                    <i class="fa fa-chevron-left"></i><?php echo tr(' Vai a fattura anticipo'); ?>
                </a>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<?php
    $acconto_righe = $dbo->fetchOne(
        'SELECT idacconto, idfattura, sum(importo_fatturato) as da_stornare
        FROM ac_acconti_righe
        WHERE idacconto = '.prepare($ac_acconti['id']).'
        GROUP BY idacconto'
    );
?>

<?php if ($record['stato'] == 'Evaso' || $record['stato'] == 'Parzialmente evaso' || $record['stato'] == 'Fatturato' || $record['stato'] == 'Parzialmente fatturato') { ?>
    <div class="row">
        <div class="col-md-6" style="display:flex; align-items:center;">
            <div style="width:100%">
                {[ "type": "number", "label": "<?php echo tr('Anticipo ancora da stornare'); ?>", "id":"anticipo", "required":0, "readonly": "1", "value": "<?php echo $acconto_righe['da_stornare']; ?>", "icon-after": "<?php echo currency(); ?>" ]}
            </div>
        </div>
    </div>
<?php } ?>

<?php if ($record['stato'] != 'Accettato' ) { ?>
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right" onclick="salvaAnticipo()">
                <?= tr('Salva') ?>
            </button>
        </div>
    </div>
<?php } ?>



<script>
	async function salvaAnticipo() {
		anticipo = $("#anticipo").val();
        rootdir = $("#root-dir").html();

        $.ajax({
            url: rootdir + "/modules/ordini/actions.php",
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
</script>
