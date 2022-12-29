<?php

include_once __DIR__.'/init.php';

$numero_articoli = $database->fetchNum('SELECT * FROM mg_articoli WHERE (id_categoria = '.prepare($categoria['id']).' AND id_sottocategoria IS NULL) OR (id_sottocategoria = '.prepare($categoria['id']).')');
$numero_sottocategorie = $database->fetchNum('SELECT * FROM mg_categorie WHERE parent = '.prepare($categoria['id']));
$numero_articoli_totali = $database->fetchNum('SELECT * FROM mg_articoli WHERE (id_categoria = '.prepare($categoria['id']).') OR (id_sottocategoria = '.prepare($categoria['id']).')');

echo '
<form action="" method="post" role="form" id="reparto-form">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="reparto">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "name": "id_reparto", "value": "'.($record['id_reparto']).'", "values": "query=SELECT id, CONCAT(codice, \' - \', descrizione) AS descrizione FROM vb_reparti" ]}
		</div>
	</div>

	<br>

	<p>'.tr('Per aggiornare sottocategorie e/o articoli a un nuovo reparto, è necessario prima salvare').'. '.tr('"Aggiorna articoli" modifica solo gli articoli con la categoria corrente impostata (senza sottocategoria), mentre "Aggiorna sottocategorie" aggiorna ricorsivamente tutti gli articoli interessati').'.</p>
	<p>'.tr('Articoli della categoria: _TOT_', [
        '_TOT_' => numberFormat($numero_articoli, 0),
    ]).'</p>
	<p>'.tr('Sottocategorie della categoria: _TOT_', [
        '_TOT_' => numberFormat($numero_sottocategorie, 0),
    ]).'</p>
	<p>'.tr('Articoli totali della categoria: _TOT_', [
        '_TOT_' => numberFormat($numero_articoli_totali, 0),
    ]).'</p>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
		    <button type="submit" class="btn btn-warning" onclick="aggiornaSottocategorie()">
			    <i class="fa fa-refresh"></i> '.tr('Aggiorna sottocategorie').'
			</button>

		    <button type="submit" class="btn btn-info" onclick="aggiornaArticoli()">
			    <i class="fa fa-refresh"></i> '.tr('Aggiorna articoli').'
			</button>

			<button type="submit" class="btn btn-primary pull-right" onclick="aggiornaCategoria()">
			    <i class="fa fa-save"></i> '.tr('Salva').'
			</button>
		</div>
	</div>
</form>

<script>
function aggiornaArticoli() {
    aggiornaOperazione("articoli");
}

function aggiornaSottocategorie() {
    aggiornaOperazione("sottocategorie");
}

function aggiornaCategoria() {
    aggiornaOperazione("reparto");
}

function aggiornaOperazione(op) {
    $("#reparto-form [name=op]").val(op);
    $("#reparto-form").submit();
}
</script>';
