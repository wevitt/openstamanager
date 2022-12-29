<?php

use Carbon\Carbon;
use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

$data = Carbon::now();
$numero = Vendita::getNextNumero($data);

echo '
<form action="" method="post" id="add-form">
    <input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			 {[ "type": "span", "label": "'.tr('Numero').'", "name": "numero", "value": "'.$numero.'" ]}
		</div>

		<div class="col-md-6">
			 {[ "type": "span", "label": "'.tr('Data e ora').'", "name": "data", "class": "text-center", "value": "'.timestampFormat($data).'" ]}
		</div>

	</div>
	
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary" id="insert">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
            <br>
            <p class="pull-left"><small>*'.tr('Premi due volte "Invio" per procedere').'</small></p>
		</div>
	</div>
</form>

<script type="text/javascript">
    $(document).ready(function(){
        $("#insert").focus();';

$salta_popup = setting('Salta automaticamente pop-up inserimento nuova vendita');
if (!empty($salta_popup)) {
    echo "
        $('form').submit();";
}

echo '
    });
</script>';
