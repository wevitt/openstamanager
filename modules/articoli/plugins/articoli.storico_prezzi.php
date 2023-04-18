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

$modifiche = $dbo->fetchArray(
    'SELECT
    an_anagrafiche.ragione_sociale as modificato_da,
    CASE
        WHEN mg_listini.nome IS NULL THEN mg_storico_prezzi_articoli.tipo_prezzo
        ELSE mg_listini.nome
    END as nome_prezzo,
    mg_storico_prezzi_articoli.prezzo,
    DATE(mg_storico_prezzi_articoli.created_at) as data,
    TIME(mg_storico_prezzi_articoli.created_at) as ora
    FROM mg_storico_prezzi_articoli
    LEFT JOIN mg_listini ON mg_storico_prezzi_articoli.idlistino = mg_listini.id
    LEFT JOIN an_anagrafiche ON mg_storico_prezzi_articoli.idutente = an_anagrafiche.idanagrafica
    WHERE mg_storico_prezzi_articoli.idarticolo = '.prepare($id_record).'
    ORDER BY nome_prezzo, data, ora'
);

$tipi_prezzi = [];
foreach ($modifiche as $modifica) {
    $tipi_prezzi[$modifica['nome_prezzo']][] = [
        'modificato_da' => $modifica['modificato_da'],
        'incremento' => 0,
        'prezzo' => $modifica['prezzo'],
        'data' => $modifica['data'],
        'ora' => $modifica['ora'],
    ];
}

?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo tr('Articolo'); ?></h3>
    </div>

    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <span><b><?php echo tr("Codice: ") ?></b><?php echo $articolo->codice ?></span>
            </div>

            <div class="col-md-6">
                <span><b><?php echo tr("Descrizione: ") ?></b><?php echo $articolo->descrizione ?></span>
            </div>
        </div>
    </div>
</div>


<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?php echo tr('Storico prezzi') ?></h3>
        <div class="pull-right">
    </div>

    <div class="box-body">
        <table class="table table-hover table-condensed table-bordered" id="tbl-concatenati">
            <thead>
                <tr>
                    <th class="text-center"><?php echo tr('Tipo prezzo'); ?></th>
                    <th class="text-center"><?php echo tr('Prezzo'); ?></th>
                    <th class="text-center"><?php echo tr('Incremento'); ?></th>
                    <th class="text-center"><?php echo tr('Modificato da'); ?></th>
                    <th class="text-center"><?php echo tr('Data modifica'); ?></th>
                    <th class="text-center"><?php echo tr('Ora modifica'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tipi_prezzi as $nome => $tipo_prezzo) { ?>
                    <tr style="background-color: #e9ecef;">
                        <td class="text-center"><strong><?php echo $nome ?></strong></td>
                        <td class="text-center" colspan="5"></td>
                    </tr>
                    <?php foreach ($tipo_prezzo as $i => $storico_prezzo) { ?>
                        <?php
                             if ($i > 0) {
                                $storico_prezzo['incremento'] = (($storico_prezzo['prezzo'] / $tipo_prezzo[$i - 1]['prezzo'])-1) * 100;
                                if ($storico_prezzo['incremento'] > 0) {
                                    $class_td = 'success';
                                    $class_icon = 'fa fa-arrow-up text-success';
                                } else {
                                    $class_td = 'danger';
                                    $class_icon = 'fa fa-arrow-down text-danger';
                                }
                            } else {
                                $class_td = '';
                                $class_icon = '';
                            }
                        ?>
                        <tr>
                            <td></td>
                            <td class="text-center"><?php echo moneyFormat($storico_prezzo['prezzo']); ?></td>
                            <td class="text-center <?php echo $class_td ?>">
                                <i class="<?php echo $class_icon ?>"></i>
                                <?php echo numberFormat($storico_prezzo['incremento'], 2); ?> %
                            </td>
                            <td class="text-center"><?php echo $storico_prezzo['modificato_da']; ?></td>
                            <td class="text-center"><?php echo $storico_prezzo['data']; ?></td>
                            <td class="text-center"><?php echo $storico_prezzo['ora']; ?></td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>


<script>

</script>
