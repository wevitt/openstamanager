<?php

use Modules\VenditaBanco\Vendita;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = Vendita::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_articolo',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale_imponibile' => $documento->totale,
];

// Dati della riga
$id_riga = get('riga_id');
$type = get('riga_type');
$riga = $documento->getRiga($type, $id_riga);

$result = $riga->toArray();
$result['prezzo'] = $riga->prezzo_unitario_ivato;

// Importazione della gestione dedicata
if ($riga->isDescrizione()) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
    echo App::load($file.'.php', $result, $options);
} elseif ($riga->isArticolo()) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
    // Modifica interna: utilizzo di grafica ridotta
    include_once __DIR__.'/edit_articolo.php';
} elseif ($riga->isSconto()) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
    echo App::load($file.'.php', $result, $options);
} else {
    $file = 'riga';

    $options['op'] = 'manage_riga';
    echo App::load($file.'.php', $result, $options);
}

// Tastiera
echo '
    <div class="row">
        <div class="col-md-12" id="keyboard">
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
        </div>
    </div>
    <div class="clearfix"></div>';

// jQuery Virtual Keyboard
echo '<link rel="stylesheet" href="'.$structure->fileurl('assets/dist/js/keyboard/css/keyboard.css').'"/>';

echo '<script  src="'.$structure->fileurl('assets/dist/js/keyboard/js/jquery.keyboard.min.js').'"></script>';

echo '<script  src="'.$structure->fileurl('assets/dist/js/keyboard/js/jquery.mousewheel.min.js').'"></script>';

echo '<script  src="'.$structure->fileurl('assets/dist/js/keyboard/js/jquery.keyboard.extension-all.min.js').'"></script>';

echo '<script  src="'.$structure->fileurl('assets/dist/js/keyboard/layouts/ms-Italian.min.js').'"></script>';

?>

<script>
    // https://jsfiddle.net/Mottie/jLu8ysh0/
    $("#modals > div").on( "shown.bs.modal", function(){

        $('#costo_unitario, #prezzo_unitario, #qta, #sconto, #sconto_unitario, #sconto_percentuale').keyboard({
            position: {
                of: $('#keyboard'),
                //my: 'center top',
                //at2: 'center bottom'
            },
            //layout : 'custom',
            reposition: true,
            layout : 'numpad',
            ignoreEsc: true,
            language: 'it',
            alwaysOpen: true,
            //appendLocally: false,
            //appendTo: $("#keyboard"),
            autoAccept: true,
            autoAcceptOnEsc: true,
            autoAcceptOnValid: true,
            cancelClose: true,
            closeByClickEvent: true,
            stayOpen:false,
            userClosed:true,
            //stopAtEnd: false,
            caretToEnd: false,
            usePreview: false,
            //resetDefault: true,
            // Callbacks - attach a function to any of these callbacks as desired
            //visible: function(e, keyboard, el) {
            //keyboard.shiftActive = keyboard.altActive = keyboard.metaActive = false;
            //keyboard.showKeySet();
            //},

            customLayout : {
                'normal'  : [ '1 2 3', '4 5 6', '7 8 9', ', 0 {cancel}' ],

            },
            css: {
                // input & preview
                input: 'form-control input-sm',
                // keyboard container
                container: 'center-block dropdown-menu', // jumbotron
                // default state
                buttonDefault: 'btn btn-default',
                // hovered button
                buttonHover: 'btn-primary',
                // Action keys (e.g. Accept, Cancel, Tab, etc);
                // this replaces "actionClass" option
                buttonAction: 'active',
                // used when disabling the decimal button {dec}
                // when a decimal exists in the input area
                buttonDisabled: 'disabled'
            },
            visible: function(event, keyboard, el) {
                setTimeout(function() { $('#prezzo_unitario').trigger('click');  }, 50);
            },


        }).addTyping({
            showTyping: true,
            delay: 50
        });

        $('#costo_unitario, #prezzo_unitario, #qta, #sconto, #sconto_unitario, #sconto_percentuale').attr("style","text-align:right;");

    }).on('hide.bs.modal', function() {
        // remove keyboards to free up memory
        $('.ui-keyboard').remove();
    });

    function aggiungi(){
        form = $("form");
        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=<?php echo Modules::get('Vendita al banco')['id']; ?>" ,
            type: "POST",
            data:  form.serialize(),
            success: function(data) {
                setTimeout(function(){
                    caricaContenuti();
                },300);
                $(".close").trigger("click");
            },
            error: function() {
                swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Errore nel salvataggio delle informazioni'); ?>", "error");
            }
        });
    }
</script>
