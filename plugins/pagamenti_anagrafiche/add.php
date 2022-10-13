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

$mesi = [
	'01' => 'Gennaio',
	'02' => 'Febbraio',
	'03' => 'Marzo',
	'04' => 'Aprile',
	'05' => 'Maggio',
	'06' => 'Giugno',
	'07' => 'Luglio',
	'08' => 'Agosto',
	'09' => 'Settembre',
	'11' => 'Ottobre',
	'11' => 'Novembre',
	'12' => 'Dicembre',
];

foreach ($mesi as $id => $mese) {
	$mesi_pagamento[] = [
        'id' => $id,
        'text' => $mese,
    ];
}

$giorni_pagamento = [];
for ($i = 1; $i <= 31; ++$i) {
    $giorni_pagamento[] = [
        'id' => $i,
        'text' => $i,
    ];
}

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addpagamento">

	<!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-4">
				{[ "type": "select", "label": "'.tr('Mese da posticipare').'", "name": "mese", "values": '.json_encode($mesi_pagamento).', "required": "1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Giorno riprogrammazione scadenza').'", "name": "giorno_fisso", "values": '.json_encode($giorni_pagamento).', "required": "1" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
