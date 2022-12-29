<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;
use Modules\VenditaBanco\Vendita;

echo '
<link rel="stylesheet" type="text/css" media="all" href="'.$rootdir.'/modules/vendita_easy/assets/dist/css/style.css"/>';

$id_categoria = $_SESSION['superselect']['categoria_vendita'];

if (get('barcode')) {
    $where = 'barcode LIKE"%'.get('barcode').'%"';
} elseif (get('codice')) {
    $where = 'codice LIKE"%'.get('codice').'%"';
} elseif (get('descrizione')) {
    $where = 'descrizione LIKE"%'.get('descrizione').'%"';
} else {
    $where = 'id_categoria='.prepare($id_categoria);
}


$query = 'SELECT id FROM mg_articoli WHERE attivo=1 AND '.$where;
$articoli = $dbo->fetchArray($query);

if (empty($dopcumento)) {
    $documento = Vendita::find(get('id_record'));
}

$i = 0;
$rs_art = [];
$n_articoli = 0;

if (setting('Visualizzazione articoli') == 'Griglia') {
    echo '
    <table class="table table-immagini">';

    if (!empty($articoli)) {
        foreach ($articoli as $art) {
            $articolo = Articolo::find($art['id']);
            $disabled = "";
            ++$n_articoli;

            if ( ($articolo->qta <= 0 && !setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita')) || $documento->isPagato() || !empty($documento->iddocumento) || !empty($documento->data_emissione)) {
                $disabled = 'disabled';
            }

            if ($i == 0) {
                echo '
                <tr>';
            }

            echo '
            <td class="nopadding">
                <a type="button" class="btn-block btn-immagine '.$disabled.'" onclick="aggiungi_articolo('.$articolo->id.',$(this));" >
                    <img src="'.($articolo->image ? $articolo->image : $rootdir.'/modules/vendita_easy/files/immagine.png').'" class="img-articolo">
                </a>
            </td>';

            $rs_art[$i]['id'] = $articolo->id;
            $rs_art[$i]['descrizione'] = $articolo->descrizione;
            $rs_art[$i]['qta'] = $articolo->qta;
            $rs_art[$i]['um'] = $articolo->um;
            $rs_art[$i]['prezzo'] = $articolo->prezzo_vendita_ivato;
            $rs_art[$i]['disabled'] = $disabled;

            if ($i == 3 || $n_articoli == count($articoli)) {
                //Riga descrizioni
                echo '
                </tr>
                <tr>';

                for ($index = 0; $index <= $i; ++$index) {
                    echo '
                    <td class="clickable '.$rs_art[$index]['disabled'].'" onclick="aggiungi_articolo('.$rs_art[$index]['id'].',$(this));" >'.$rs_art[$index]['descrizione'].'</td>';
                }

                //Riga prezzi
                echo '
                </tr>
                <tr>';

                for ($index = 0; $index <= $i; ++$index) {
                    echo '
                    <td>
                        <div class="pull-left">
                            <small class="'.(($rs_art[$index]['qta'] <= 0) ? 'text-red' : '').'" ><i class="fa fa-th-list"></i> '.Translator::numberToLocale($rs_art[$index]['qta'], 'qta').' '.$rs_art[$i]['um'].'</small>
                        </div>
                        <div class="pull-right">
                            <small>'.moneyformat($rs_art[$index]['prezzo']).'</small>
                        </div>
                        <div class="clearfix"></div>
                    </td>';
                }

                echo '
                </tr>';

                $i = 0;
            } else {
                ++$i;
            }
        }
    } else {
        echo '
        <tr>
            <td colspan="4" class="col-md-12 text-center">
                '.tr('Nessun articolo trovato per la categoria scelta').'!
            </td>
        </tr>';
    }

    echo '
    </table>';
} else {
    echo '
    <table class="table table-striped">
        <tr>
            <th class="text-center">Codice</th>
            <th class="text-center">Descrizione</th>
            <th class="text-center">Prezzo</th>
            <th class="text-center">Q.tà</th>
        </tr>';

    if (!empty($articoli)) {
        foreach ($articoli as $art) {
            $articolo = Articolo::find($art['id']);
            ++$n_articoli;

            if ($articolo->qta <= 0 || $documento->isPagato() || !empty($documento->iddocumento) || !empty($documento->data_emissione)) {
                $disabled = 'disabled';
            }

            echo '
        <tr>';

            echo '
            <td class="clickable text-center" onclick="aggiungi_articolo('.$articolo->id.',$(this));" style="width:30%;">
                '.$articolo->codice.'
            </td>
            <td class="clickable text-center" onclick="aggiungi_articolo('.$articolo->id.',$(this));">
                '.$articolo->descrizione.'
            </td>
            <td class="clickable text-center" onclick="aggiungi_articolo('.$articolo->id.',$(this));" style="width:20%;">
                '.moneyformat($articolo->prezzo_vendita).'
            </td>
            <td class="clickable text-center" onclick="aggiungi_articolo('.$articolo->id.',$(this));" style="width:15%;">
                '.Translator::numberToLocale($articolo->qta).'
            </td>';

            echo '
        </tr>';
        }
    } else {
        echo '
        <tr>
            <td colspan="4" class="col-md-12 text-center">
                '.tr('Nessun articolo trovato per la categoria scelta').'!
            </td>
        </tr>';
    }

    echo '
    </table>';
}

?>

<script>

    function aggiungi_articolo(idarticolo,btn){
        btn.addClass("disabled");
        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.id_module ,
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "add_articolo",
                idarticolo: idarticolo,
            },
            success: function (response) {
                if (!response.result) {
                    swal("<?php echo tr('Errore'); ?>", response.message, "error");
                }
            },
            error: function() {
                swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Errore nel salvataggio delle informazioni'); ?>", "error");
            }
        });

        setTimeout(function(){
            caricaContenuti();
            btn.removeClass("disabled");
            $(".close").trigger("click");
        },300);
    }

</script>
