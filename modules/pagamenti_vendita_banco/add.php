<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add_element">
	<input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "'.tr('Tipologia').'", "name": "tipologia", "required": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Elemento collegato').'", "name": "id_element", "required": 1, "ajax-source": "pagamenti_vendite" ]}
        </div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button class="btn btn-primary">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>';
