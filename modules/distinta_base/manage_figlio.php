<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

$id_articolo = get('id_record');
$id_figlio = get('id_articolo');

$articolo = Articolo::find($id_articolo);

$figlio = $dbo->fetchOne('SELECT qta FROM mg_articoli_distinte WHERE id_articolo='.prepare($id_articolo).' AND id_figlio='.prepare($id_figlio));

$add = empty($id_figlio);

echo '
<p>'.tr("Se il componente selezionato è già presente nella composizione dell'articolo _DESC_ ne verrà aggiornata la quantità", [
    '_DESC_' => $articolo->codice.' - '.$articolo->descrizione,
]).'.</p>

<form action="" method="post">
	<input type="hidden" name="op" value="manage_figlio">
    <input type="hidden" name="backto" value="record-edit">

	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_articolo" value="'.$id_articolo.'">

	<div class="row">
		<div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_figlio", "id": "idarticolo", "required": 1, "value": "'.$id_figlio.'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1, "dir": "entrata"}, "disabled": "'.intval(!$add).'", "icon-after": "add|'.Modules::get('Articoli')['id'].'" ]}
		</div>

        <div class="col-md-6">
            {[  "type": "number", "label": "'.tr('Qta').'", "name": "qta", "required": 1, "value": "'.$figlio['qta'].'" ]}
        </div>
	</div>

    <div class="clearfix"></div>
    <br>
    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-primary pull-right">';

if ($add) {
    echo '
                <i class="fa fa-plus"></i> '.tr('Aggiungi');
} else {
    echo '
                <i class="fa fa-edit"></i> '.tr('Modifica');
}

echo '
            </button>
        </div>
    </div>
</form>

<script>$(document).ready(init)</script>';
