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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="updateprovvigione">

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Articolo').'", "name": "articolo", "ajax-source": "articoli", "value": "'.$id_parent.'", "disabled": "1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "value": "$idagente$", "values": "query=SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = \'\', \'\', CONCAT(\' (\', citta, \')\')), IF(deleted_at IS NULL, \'\', \' ('.tr('eliminata').')\')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE deleted_at IS NULL AND descrizione=\'Agente\' AND an_anagrafiche.idanagrafica NOT IN (SELECT idagente FROM co_provvigioni WHERE idagente!='.prepare($record['idagente']).') ORDER BY ragione_sociale", "required": 1, "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica=Agente&readonly_tipo=1" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "number", "label": "'.tr('Provvigione').'", "name": "provvigione", "value": "'.$record['provvigione'].'", "icon-after": "choice|untprc|'.$record['tipo_provvigione'].'" ]}
		</div>
	</div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask" data-backto="record-edit" data-op="deleteprovvigione" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_module="'.$id_module.'" data-id_parent="'.$id_parent.'">
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-success pull-right"><i class="fa fa-check"></i> '.tr('Salva').'</button>
		</div>
	</div>
</form>';
