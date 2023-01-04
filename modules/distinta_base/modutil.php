<?php

include_once __DIR__.'/../../core.php';
require __DIR__.'/vendor/autoload.php';

use Modules\Articoli\Articolo;

/**
 * Funzione ricorsiva per il rendering della tabella della distinta base.
 *
 * @param null $id_parent
 * @param int  $level
 */
function renderDistinta(Articolo $articolo, $id_parent = null, $parent_qta = 1, $level = 0)
{
    global $dbo;

    $componenti = $articolo->componenti;

    $margin = $level * 30;
    $color = 190 + ($level * 15);

    if ($level != 0) {
        $hex = sprintf('#%02x%02x%02x', $color, $color, $color);
    } else {
        $hex = '#92bcf2';
    }

    echo '
<tr style="background-color: '.$hex.'">
    <td class="search_articolo">
        <div style="margin-left:'.$margin.'px">
            '.Modules::link(Modules::get('Articoli')['id'], $articolo['id'], $articolo['codice'].' - '.$articolo['descrizione']).'
        </div>
    </td>';

    $fornitore = $dbo->fetchOne('SELECT idanagrafica, ragione_sociale FROM an_anagrafiche WHERE idanagrafica='.prepare($articolo->id_fornitore));

    echo '
    <td class="search_fornitore">';

    if (!empty($fornitore)) {
        echo Modules::link(Modules::get('Anagrafiche')['id'], $fornitore['idanagrafica'], $fornitore['ragione_sociale']);
    }

    echo '
    </td>';

    $qta = $articolo->pivot->qta ?: 1;

    echo '
    <td class="text-right">
        '.Translator::numberToLocale($parent_qta * $qta).'
    </td>
    
    <td class="text-right">';
    if ($level == 0) {
        echo '-';
    } else {
        if ($level == 1) {
            echo '
  
        '.moneyFormat($articolo['prezzo_acquisto'] * $qta).'<br>
        <small class="help-block">'.moneyFormat($articolo['prezzo_acquisto']).' x '.numberFormat($qta, 'qta').'</small>';
        } else {
            echo '
        <small class="help-block">'.moneyFormat($articolo['prezzo_acquisto']).'</small>';
        }
    }
    echo '
    </td>
    
    <td class="text-right">';
    if ($level == 0) {
        echo '-';
    } else {
        if ($level == 1) {
            echo '
        '.moneyFormat($articolo['prezzo_vendita'] * $qta).'<br>
        <small class="help-block">'.moneyFormat($articolo['prezzo_vendita']).' x '.numberFormat($qta, 'qta').'</small>';
        } else {
            echo '
        <small class="help-block">'.moneyFormat($articolo['prezzo_vendita']).'</small>';
        }
    }
    echo '
    </td>

    <td class="text-center">
        <div class="btn-group">
            <button class="btn btn-secondary btn-xs btn-primary" onclick="add_articolo('.$articolo['id'].')">
                <i class="fa fa-plus"></i>
            </button>';

    if ($level != 0) {
        echo '

            <button class="btn btn-secondary btn-xs btn-warning" onclick="edit_articolo('.$id_parent.', '.$articolo['id'].')">
                <i class="fa fa-edit"></i>
            </button>

            <button class="btn btn-secondary btn-xs btn-danger" onclick="delete_articolo('.$id_parent.', '.$articolo['id'].')">
                <i class="fa fa-trash"></i>
            </button>';
    }

    echo '
        </div>
    </td>
<tr>';

    // Ricorsione
    foreach ($componenti as $componente) {
        renderDistinta($componente, $articolo['id'], $qta, $level + 1);
    }
}
