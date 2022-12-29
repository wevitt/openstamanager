<?php

include_once __DIR__.'/../../core.php';

$elementi = $dbo->fetchArray('SELECT * FROM `co_pagamenti` WHERE tipo_xon_xoff = '.prepare($id_record).' GROUP BY descrizione');

if (empty($elementi)) {
    echo '
<p>'.tr('Nessun elemento collegato alla tipologia').'.</p>';

    return;
}

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <thead>
        <tr>
			<th>'.tr('Metodi di pagamento').'</th>
            <th width="60" class="text-center">#</th>
		</tr>
	</thead>

    <tbody>';

foreach ($elementi as $elemento) {
    //Controllo se il pagamento è collegato ad una tipologia
    $disabled = $dbo->fetchOne("SELECT 'disabled' AS descrizione FROM vb_venditabanco WHERE idpagamento=".prepare($elemento['id']))['descrizione'];
    echo '
        <tr data-id="'.$elemento['id'].'">
            <td>'.Modules::link(Modules::get('Pagamenti')['id'], $elemento['id'], $elemento['descrizione']).'</td>
            <td class="text-center">
                <button class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)" '.$disabled.'>
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>';
}
echo '
    </tbody>
</table>';
