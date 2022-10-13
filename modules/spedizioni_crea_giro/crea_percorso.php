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

$nome_percorso = filter('nome_percorso');
$note = filter('note');
$punto_di_partenza = filter('punto_di_partenza');
$punto_di_arrivo = filter('punto_di_arrivo');
$data_di_partenza = filter('data_di_partenza');
$orario_di_partenza = filter('orario_di_partenza');
?>


<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add-mio-percorso">
	<input type="hidden" name="backto" value="record-list">

    <input type="hidden" name="percorso" class="percorso">
    <input type="hidden" name="note" class="note" value="<?= $note ?>">
    <input type="hidden" class="punto_di_arrivo" value="<?= $punto_di_arrivo ?>">

    <div class="row" style="margin-top:15px">
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Nome percorso'); ?>", "name": "nome_percorso", "readonly": 1, "value":"<?= $nome_percorso ?>" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "text", "label": "<?php echo tr('Punto di partenza'); ?>", "name": "punto_di_partenza", "readonly": 1, "value":"<?= $punto_di_partenza ?>" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "date", "label": "<?php echo tr('Data di partenza'); ?>", "name": "data_di_partenza", "readonly": 1, "value":"<?= $data_di_partenza ?>" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "time", "label": "<?php echo tr('Orario di partenza'); ?>", "name": "orario_di_partenza", "readonly": 1, "value":"<?= $orario_di_partenza ?>" ]}
        </div>
	</div>

    <table class="tbl-mio-percorso table-rate table table-hover table-striped">
        <thead>
            <tr>
                <th style="display:none"><?php echo tr('Numero'); ?></th>
                <th><?php echo tr(''); ?></th>
                <th><?php echo tr(''); ?></th>
                <th><?php echo tr('Ragione sociale'); ?></th>
                <th><?php echo tr('Indirizzo'); ?></th>
                <th><?php echo tr('Città'); ?></th>
                <th><?php echo tr('Colli'); ?></th>
                <th><?php echo tr('Azioni'); ?></th>
                <th style="display:none"></th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot style="display: none">
            <tr>
                <td class="id" style="display:none"></td>
                <td class="numero-documento" style="display:none"></td>
                <td class="up" onclick="moveUp($(this))" style="cursor:pointer"><i class="fa fa-arrow-up" aria-hidden="true"></i></td>
                <td class="down" onclick="moveDown($(this))" style="cursor:pointer"><i class="fa fa-arrow-down" aria-hidden="true"></i></td>
                <td class="ragione-sociale"></td>
                <td class="indirizzo"></td>
                <td class="citta"></td>
                <td class="cap" style="display:none"></td>
                <td class="provincia" style="display:none"></td>
                <td class="colli"></td>
                <td class="azioni"></td>
                <td class="inputs" style="display:none">
                    <input type="hidden" name="id[]">
                    <input type="hidden" name="numero_documento[]">
                    <input type="hidden" name="tipo_consegna[]">
                    <input type="hidden" name="ragione_sociale[]">
                    <input type="hidden" name="indirizzo[]">
                    <input type="hidden" name="citta[]">
                    <input type="hidden" name="cap[]">
                    <input type="hidden" name="provincia[]">
                    <input type="hidden" name="tempo[]">
                    <input type="hidden" name="arrivo[]">
                    <input type="hidden" name="km[]">
                    <input type="hidden" name="colli[]">
                </td>
            </tr>
        </tfoot>
    </table>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
            <a class="btn btn-primary btn-aggiungi-tappa" onclick="aggiungiNuovaTappa()"><i class="fa fa-plus"></i> <?php echo tr('Aggiungi tappa'); ?></a>
			<button type="submit" class="btn btn-primary"><?php echo tr('Salva'); ?></button>
		</div>
	</div>
</form>

<script>
    $(document).ready(function() {
        loadTappe();
    });

    function loadTappe() {
        var $rows = $(".dataTables_scrollBody").find("tbody");

        var $body = $('.tbl-mio-percorso').find('tbody');
        var $template = $('.tbl-mio-percorso').find('tfoot');

        var i = 0;
        $rows.find(".selected").each(function() {
            item = {
                id: $(this).find('span').attr('data-id'),
                    tipo_consegna: $(this).find("td:nth-child(2)").text(),
                    numero: $(this).find("td:nth-child(3)").text(),
                    ragione_sociale: $(this).find("td:nth-child(4)").text(),
                    indirizzo: $(this).find("td:nth-child(6)").text(),
                    citta: $(this).find("td:nth-child(7)").text(),
                    cap: $(this).find("td:nth-child(8)").text(),
                    provincia: $(this).find("td:nth-child(9)").text(),
                    colli: $(this).find("td:nth-child(10)").text(),
                }

            $template.find('.id').text(item.id);
            $template.find('.numero-documento').text(item.numero);
            $template.find('.ragione-sociale').text(item.ragione_sociale);
            $template.find('.indirizzo').text(item.indirizzo);
            $template.find('.citta').text(item.citta);
            $template.find('.cap').text(item.cap);
            $template.find('.provincia').text(item.provincia);
            $template.find('.colli').text(item.colli);
            $template.find('.azioni').html('<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>');

            $inputs = $template.find('.inputs');
            $inputs.find('[name="id[]"]').val(item.id);
            $inputs.find('[name="numero_documento[]"]').val(item.numero);
            $inputs.find('[name="tipo_consegna[]"]').val(item.tipo_consegna);
            $inputs.find('[name="ragione_sociale[]"]').val(item.ragione_sociale);
            $inputs.find('[name="indirizzo[]"]').val(item.indirizzo);
            $inputs.find('[name="citta[]"]').val(item.citta);
            $inputs.find('[name="cap[]"]').val(item.cap);
            $inputs.find('[name="provincia[]"]').val(item.provincia);
            $inputs.find('[name="colli[]"]').val(item.colli);

            $body.append($template.html());

            i++;
        });

        if ($('.punto_di_arrivo').val()) {
            var array = ($('[name=punto_di_partenza]').val()).split(',');

            $template.find('.id').text('');
            $template.find('.numero-documento').text('');
            $template.find('.ragione-sociale').text('');
            $template.find('.indirizzo').text(array[0]);
            $template.find('.citta').text(array[1]);
            $template.find('.cap').text('');
            $template.find('.provincia').text('');
            $template.find('.colli').text('');
            $template.find('.azioni').html('<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>');

            $inputs = $template.find('.inputs');
            $inputs.find('[name="id[]"]').val('');
            $inputs.find('[name="numero_documento[]"]').val('');
            $inputs.find('[name="tipo_consegna[]"]').val('');
            $inputs.find('[name="ragione_sociale[]"]').val('');
            $inputs.find('[name="indirizzo[]"]').val(array[0]);
            $inputs.find('[name="citta[]"]').val(array[1]);
            $inputs.find('[name="cap[]"]').val('');
            $inputs.find('[name="provincia[]"]').val('');
            $inputs.find('[name="colli[]"]').val('');

            $body.append($template.html());
        }

    }

    function aggiungiNuovaTappa() {
        if ($('.btn-aggiungi-tappa').attr('disabled') == undefined) {
            disableAggiungiTappa(true);

            var $table = $(".tbl-mio-percorso");
            var $body = $table.find('tbody');
            var $template = $table.find('tfoot');

            $template.find('.id').text('');
            $template.find('.numero-documento').text('');
            $template.find('.numero').text('');
            $template.find('.ragione-sociale').text('');
            $template.find('.indirizzo').html('<input type="text" class="form-control" name="autocomplete-address"/>');
            $template.find('.citta').html('');
            $template.find('.cap').text('');
            $template.find('.provincia').text('');
            $template.find('.tempo').text('');
            $template.find('.arrivo').text('');
            $template.find('.km').text('');
            $template.find('.colli').text('');
            $template.find('.azioni').html(
                    '<a class="btn" onclick="inserisciTappa($(this))"><i class="fa fa-check" aria-hidden="true"></i></a>' +
                    '<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>'
                );
            $inputs = $template.find('.inputs');
            $inputs.find('[name="id[]"]').val('');
            $inputs.find('[name="numero_documento[]"]').val('');
            $inputs.find('[name="tipo_consegna[]"]').val('');
            $inputs.find('[name="ragione_sociale[]"]').val('');
            $inputs.find('[name="indirizzo[]"]').val('');
            $inputs.find('[name="citta[]"]').val('');
            $inputs.find('[name="cap[]"]').val('');
            $inputs.find('[name="provincia[]"]').val('');
            $inputs.find('[name="tempo[]"]').val('');
            $inputs.find('[name="arrivo[]"]').val('');
            $inputs.find('[name="km[]"]').val('');
            $inputs.find('[name="colli[]"]').val('');

            $body.append($template.html());
            autocompleteIndirizzo();
        }
    }

    function rimuoviTappa($this) {
        var $row = $this.closest('tr');
        $row.remove();
        disableAggiungiTappa(false);
    }

    function inserisciTappa($this) {
        $('.is-invalid').removeClass("is-invalid");
        var $row = $this.closest('tr');

        var indirizzo = $row.find('.indirizzo input').val();
        if (!indirizzo) {
            $row.find('.indirizzo input').addClass('is-invalid');
        } else {
            var array = indirizzo.split(',');
            if (array.length < 4) {
                $row.find('.indirizzo input').addClass('is-invalid');
            } else {
                var i = 0
                if (array.length == 5) { //è indicato anche il numero civico
                    $row.find('.indirizzo').text(array[i] + ' ' + array[i+1]);
                    i++;
                } else {
                    $row.find('.indirizzo').text(array[i]);
                }
                $row.find('.citta').text(array[i+1]);
                $row.find('.cap').text(array[i+2]);
                $row.find('.provincia').text(array[i+2]);
                $row.find('.azioni').html('<a class="btn" onclick="rimuoviTappa($(this))"><i class="fa fa-trash" aria-hidden="true"></i></a>');

                disableAggiungiTappa(false);
            }
        }
    }


    /**
     * UTILITY
     */

    function moveDown($this) {
        var $row = $this.closest('tr');
        $row.next().after($row);
        //disableButtons(false);
    }

    function moveUp($this) {
        var $row = $this.closest('tr');
        $row.prev().before($row);
        //disableButtons(false);
    }

    function disableAggiungiTappa(flag) {
        if (flag) {
            $('.btn-aggiungi-tappa').attr("disabled", "disabled",);
        } else {
            $('.btn-aggiungi-tappa').removeAttr("disabled");
        }
    }

</script>
