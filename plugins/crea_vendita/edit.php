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

use Modules\DDT\DDT;
use Modules\Interventi\Intervento;

$module = Modules::get($id_module);

if ($module->name == 'Interventi') {
    $name = 'intervento';
    $documento = Intervento::find($id_record);

    $block = ($documento->stato->is_fatturabile ? '' : 'disabled');
    $stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `in_statiintervento` WHERE `is_fatturabile` = 1 ')['stati_abilitati'];

    echo '
        <div class="alert alert-info">
            '.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine deve essere tra: _STATE_LIST_", [
                '_STATE_LIST_' => $stati_abilitati,
            ]).'
        </div>';
} elseif ($module->name == 'Preventivi') {
    $name = 'preventivo';

    $rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record));
    $block = (($record['is_fatturabile'] && !empty($rs_documento)) ? '' : 'disabled');
    $stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_statipreventivi` WHERE `is_fatturabile` = 1 ')['stati_abilitati'];

    echo '
        <div class="alert alert-info">
            '.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine deve essere tra: _STATE_LIST_", [
                '_STATE_LIST_' => $stati_abilitati,
            ]).'
        </div>';
} if ($module->name == 'Ddt di vendita') {
    $name = 'ddt';
    $documento = DDT::find($id_record);
    $block = ($documento ? ($documento->isImportabile() ? '' : 'disabled') : '');
    $stati_abilitati = 'Evaso, Parzialmente evaso, Parzialmente fatturato';
    echo '
        <div class="alert alert-info">
            '.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine deve essere tra: _STATE_LIST_", [
                '_STATE_LIST_' => $stati_abilitati,
            ]).'
        </div>';
} if ($module->name == 'Ordini cliente') {
    $name = 'ordine';
    $block = (!in_array($record['stato'], ['Fatturato', 'Evaso', 'Bozza', 'In attesa di conferma', 'Annullato']) ? '' : 'disabled');

    $stati_abilitati = ['Fatturato', 'Evaso', 'Bozza', 'In attesa di conferma', 'Annullato'];
    echo '
        <div class="alert alert-info">
            '.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine non deve essere tra: _STATE_LIST_", [
                '_STATE_LIST_' => implode(', ', $stati_abilitati),
            ]).'
        </div>';
}

echo '    
<div class="row">
    <div class="col-md-12 text-center">
        <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'" data-toggle="modal" data-title="'.tr('Crea vendita al banco').'" class="btn btn-lg btn-primary '.$block.'" '.$block.'>
            <i class="fa fa-wrench"></i> '.tr('Crea vendita al banco').'
        </a>
    </div>
</div>';
