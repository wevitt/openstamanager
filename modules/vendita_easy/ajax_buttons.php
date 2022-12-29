<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

if ($is_pagato) {
    $disabled = 'disabled';
}

echo '
<style>
    #menu_scomparsa{
        position:absolute;
        bottom:48% !important;
        right:43% !important;
        z-index:5;
    }
</style>';

echo '
<div class="col-md-7" id="menu_scomparsa">
    <ul class="dropdown-menu dropdown-menu-right" id="print_btn">';
        echo '
        <li>
            <a type="button" class="btn btn-default '.($is_pagato && setting("Stampa scontrino fiscale con vendita aperta") && $stampante_fiscale->isConfigured() ? '' : 'disabled').'" onclick="stampaDocumento(this, \'xonxoff\', 0, 1)">
                <i class="fa fa-file-o text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Copia scontrino').'</p>
            </a>
        </li>

        <li>
            <a type="button" class="btn btn-default '.($stampante_non_fiscale->isConfigured() ? '' : 'disabled').'" onclick="stampaDocumento(this, \'txt\', 0)">
                <i class="fa fa-file-text-o  text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Comanda').'</p>
            </a>
        </li>

        <li>
            <a type="button" class="btn btn-default '.($stampante_non_fiscale->isConfigured() && !$stampante_fiscale->isConfigured() ? '' : 'disabled').'" onclick="stampaDocumento(this, \'txt\', 0, 1)">
                <i class="fa fa-file-o text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Preconto').'</p>
            </a>
        </li>
        
        <li>
            <a type="button" class="btn btn-default '.$disabled.'" onclick="launch_modal(\''.tr('Aggiungi').'...\', \''.$rootdir.'/modules/vendita_easy/add_fattura.php?id_record='.$id_record.'\');">
                <i class="fa fa-file text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Fattura').'</p>
            </a>
        </li>
    </ul>

    <ul class="dropdown-menu dropdown-menu-right text-center" id="righe_btn" style="right:10%;">
        <li>
            <a type="button" class="btn btn-default " onclick="gestioneRiga(this,\'is_riga\');">
                <i class="fa fa-file-text  text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Riga').'</p>
            </a>
        </li>
        <li>
            <a type="button" class="btn btn-default" onclick="gestioneSconto(this);">
                <i class="fa fa-percent text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Sconto').'</p>
            </a>
        </li>
        <li>
            <a type="button" class="btn btn-default" onclick="gestioneDescrizione(this);">
                <i class="fa fa-file-text-o  text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Descrizione').'</p>
            </a>
        </li>
    </ul>
</div>';

echo '
<div class="col-md-7" id="div-menu">
        <button type="button" class="btn btn-default dropdown-toggle btn-stampa pull-right btn-menu-bottom" onclick="if( $(\'#print_btn\').is(\':visible\') ){$(\'#print_btn\').hide();}else{$(\'#print_btn\').show();$(\'#righe_btn\').hide();}" ><i class="fa fa-print text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Stampa').'<span class="caret"></span></p>
            <span class="sr-only">Toggle Dropdown</span>
        </button>';       

        //Aggiunta righe
        echo '
        <button type="button" class="btn btn-default dropdown-toggle btn-righe pull-right btn-menu-bottom" style="padding:23px;" onclick="if( $(\'#righe_btn\').is(\':visible\') ){$(\'#righe_btn\').hide();}else{$(\'#righe_btn\').show();$(\'#print_btn\').hide();}" '.$disabled.'><i class="fa fa-file-text text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Righe').'<span class="caret"></span></p>
            <span class="sr-only">Toggle Dropdown</span>
        </button>

        <a type="button" class="btn btn-default pull-right btn-menu-bottom '.(!$is_pagato ? '' : 'hide').'" onclick="chiusuraFiscale(this);" '.$disabled.'>
            <i class="fa fa-book text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Chiusura').'</p>
        </a>

        <a type="button" class="btn btn-default pull-right btn-menu-bottom '.($is_pagato ? '' : 'hide').'" onclick="riaperturaFiscale(this);" >
            <i class="fa fa-book text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Riapertura').'</p>
        </a>

        <a type="button" class="btn btn-default pull-right btn-menu-bottom" onclick="launch_modal(\''.tr('Cerca').'\', \''.$rootdir.'/modules/vendita_easy/cerca.php?id_record='.$id_record.'\');">
            <i class="fa fa-barcode text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Cerca').'</p>
        </a>

        <a type="button" class="btn btn-default pull-right btn-menu-bottom" onclick="aperturaCassetto();">
            <i class="fa fa-arrow-down text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Apri Cassetto').'</p>
        </a>

        <a type="button" class="btn btn-default pull-right btn-menu-bottom '.$disabled.'" onclick="movimenta();" >
            <i class="fa fa-truck  text-primary fa-3x"></i><br>
            <p class="text-primary">'.tr('Movimenta').'</p>
        </a>
</div>
<div class="col-md-2 text-right" id="div-buttons">
    <button id="btn_cancella" type="button" class="btn btn-block btn-default '.$disabled.'" onclick="rimuoviRighe();" '.$disabled.'><b>'.tr('CANCELLA').'</b></button>
    <button type="button" class="btn btn-block btn-default" id="btn_attesa"><b>'.tr('IN ATTESA').'</b></button>
</div>

<div class="col-md-3" id="div-paga">
    <div class="dropup">
        <button class="btn btn-block btn-primary dropdown-toggle '.$disabled.'" type="button" id="btn_paga" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <b>'.tr('PAGA').'</b>
        </button>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <a type="button" class="btn btn-default btn-block '.$disabled.'" onclick="launch_modal(\''.tr('Paga').'\', globals.rootdir + \'/modules/vendita_easy/paga.php?id_record=\' + globals.id_record);">
                <i class="fa fa-credit-card-alt text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Paga e stampa lo scontrino').'</p>
            </a>
            <a type="button" class="btn btn-default btn-block '.$disabled.'" onclick="launch_modal(\''.tr('Paga').'\', globals.rootdir + \'/modules/vendita_easy/paga.php?id_record=\' + globals.id_record + \'&ritorna=1\');">
                <i class="fa fa-credit-card-alt text-primary fa-3x"></i><br>
                <p class="text-primary">'.tr('Paga e stampa lo scontrino <br>e torna alla lista').'</p>
            </a>
        </div>
    </div>
</div>';