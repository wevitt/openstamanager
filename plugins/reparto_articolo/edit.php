<?php

include_once __DIR__.'/init.php';

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="reparto">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "name": "id_reparto", "value": "'.($articolo->id_reparto).'", "values": "query=SELECT id, CONCAT(codice, \' - \', descrizione) AS descrizione FROM vb_reparti" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-save"></i> '.tr('Salva').'
			</button>
		</div>
	</div>
</form>';
