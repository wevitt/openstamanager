$(document).ready(function () {
    $("#pulsanti-modulo, #save, #pulsanti, #tabs, .main-header, .main-footer").hide();
    $(".btn-default:last").hide();
    if (!$("body").hasClass("sidebar-collapse")) {
        $(".sidebar-toggle").trigger("click");
    }

    if ($(".main-footer").hasClass("with-control-sidebar")) {
        $(".control-sidebar-toggle").click()
    }

    $(".content-wrapper").css("min-height", "0");

    caricaContenuti();
});

$(document).on("keyup", function (event) {
    if ($(":focus").is("input, textarea")) {
        return;
    }

    let key = window.event ? event.keyCode : event.which; // IE vs Netscape/Firefox/Opera
    let barcode = $("#barcode_reader");

    if (barcode.val() === "" && key === 13) {
        swal(globals.vendita_easy.barcode, "", "warning");
    } else if (key === 13) {
        let search = barcode.val().replace(/[^a-z0-9\s\-.\/\\|]+/gmi, "");
        aggiungiBarcode(search);
    } else if (key === 8) {
        barcode.val(barcode.val().substr(0, barcode.val().length - 1));
    } else if (key <= 90 && key >= 48) {
        barcode.val(barcode.val() + String.fromCharCode(key));
    }
});

$(".btn-categoria").click(function () {
    const id = $(this).attr("id");

    // Correzione grafica dell'elenco categorie
    $(".btn-categoria").each(function () {
        const $this = $(this);

        if ($this.attr("id") === id) {
            $this.prop("disabled", true)
                .removeClass("btn-default")
                .addClass("btn-primary");
        } else {
            $this.prop("disabled", false)
                .removeClass("btn-primary")
                .addClass("btn-default");
        }
    });

    // Caricamento degli Articoli della categoria
    session_set("superselect,categoria_vendita", id, 0);
    caricaArticoli();
});

function caricaRighe() {
    let container = $("#row-list");
    container.html(`<i class="fa fa-gear fa-spin"></i> ` + globals.translations.loading + "...");

    localLoading(container, true);
    return $.get(globals.vendita_easy.urls.righe, function (data) {
        container.html(data);
        localLoading(container, false);
    });
}

function caricaArticoli() {
    let container = $("#ajax_articoli");
    container.html(`<i class="fa fa-gear fa-spin"></i> ` + globals.translations.loading + "...");

    localLoading(container, true);
    return $.get(globals.vendita_easy.urls.articoli, function (data) {
        container.html(data);
        localLoading(container, false);
    });
}

function caricaPulsanti() {
    let container = $("#pulsanti_bottom");
    container.html(`<i class="fa fa-gear fa-spin"></i> ` + globals.translations.loading + "...");

    localLoading(container, true);
    return $.get(globals.vendita_easy.urls.pulsanti, function (data) {
        container.html(data);
        localLoading(container, false);
    });
}

function caricaCosti() {
    let container = $("#div_costi");
    container.html(`<i class="fa fa-gear fa-spin"></i> ` + globals.translations.loading + "...");

    localLoading(container, true);
    return $.get(globals.vendita_easy.urls.costi, function (data) {
        container.html(data);
        localLoading(container, false);
    });
}

function caricaContenuti() {
    $("#barcode_reader").focus();
    caricaRighe();
    caricaArticoli();
    caricaPulsanti();
    caricaCosti();
}

function rimuoviRighe() {
    swal({
        title: globals.vendita_easy.rimuoviRighe.titolo,
        text: globals.vendita_easy.rimuoviRighe.messaggio,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-danger",
        confirmButtonText: globals.vendita_easy.rimuoviRighe.conferma,
        closeOnConfirm: false
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.id_module,
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "reset_righe",
            },
            success: caricaContenuti,
        });
    });
}

function chiusuraFiscale(button) {
    openModal(globals.vendita_easy.chiusuraFiscale.titolo, globals.vendita_easy.chiusuraFiscale.url);
}

function riaperturaFiscale(button) {
    swal({
        title: globals.vendita_easy.riaperturaFiscale.titolo,
        text: globals.vendita_easy.riaperturaFiscale.messaggio,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-success",
        confirmButtonText: globals.vendita_easy.riaperturaFiscale.conferma,
        closeOnConfirm: false
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.id_module,
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update",
                chiusura: chiusura,
            },
            success: function (response) {
                if (!response.result) {
                    swal(globals.vendita_easy.errore.errore, response.message, "warning");
                }

                caricaContenuti();
            },
            error: function () {
                swal(globals.vendita_easy.errore.titolo, globals.vendita_easy.errore.messaggio, "error");
            }
        });
    });
}

function stampaDocumento(button, formato, is_fiscale, pricing) {
    let testi;
    if (is_fiscale === 0) {
        if (pricing === 1) {
            testi = globals.vendita_easy.stampa.preconto;
        } else {
            testi = globals.vendita_easy.stampa.comanda;
        }
    } else {
        testi = globals.vendita_easy.stampa.scontrino;
    }

    swal({
        title: testi.titolo,
        html: testi.messaggio,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-primary",
        confirmButtonText: globals.vendita_easy.stampa.conferma,
        closeOnConfirm: false
    }).then(function () {
        const restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.vendita_easy.id_modulo_vendita,
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.vendita_easy.id_modulo_vendita,
                id_record: globals.id_record,
                op: "stampa",
                formato: formato,
                fiscale: is_fiscale,
                pricing: pricing,
            },
            success: function (response) {
                buttonRestore(button, restore);

                if (!response.result) {
                    swal(globals.vendita_easy.errore.titolo, response.message, "error");
                }
            },
            error: function () {
                buttonRestore(button, restore);

                swal(globals.vendita_easy.errore.titolo, globals.vendita_easy.stampa.errore, "error");
            }
        });
    }).catch(swal.noop);
}

function stampaDocumentoLotteria(button, formato, is_fiscale, pricing) {
    const title = globals.vendita_easy.stampa.lotteria.titolo;
    const html = globals.vendita_easy.stampa.lotteria.messaggio + `<input type="text" class="form-control" placeholder="Codice lotteria" id="codice_lotteria">`;

    swal({
        title: title,
        html: html,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn btn-lg btn-primary",
        confirmButtonText: globals.vendita_easy.stampa.conferma,
        closeOnConfirm: false
    }).then(function () {
        const restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php?id_module=" + globals.vendita_easy.id_modulo_vendita,
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.vendita_easy.id_modulo_vendita,
                id_record: globals.id_record,
                op: "stampa",
                formato: formato,
                fiscale: is_fiscale,
                pricing: pricing,
                codice_lotteria: $("#codice_lotteria").val(),
            },
            success: function (response) {
                buttonRestore(button, restore);

                if (!response.result) {
                    swal(globals.vendita_easy.errore.titolo, response.message, "error");
                }
            },
            error: function () {
                buttonRestore(button, restore);

                swal(globals.vendita_easy.errore.titolo, globals.vendita_easy.stampa.errore, "error");
            }
        });
    }).catch(swal.noop);
}

function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione");
}

async function gestioneRiga(button, options) {
    // Apertura modal
    let title = options === "is_sconto" ? globals.vendita_easy.gestioneRighe.sconto : globals.vendita_easy.gestioneRighe.descrizione;
    options = "&"+options+"=1";
    openModal(title, globals.vendita_easy.gestioneRighe.url + options);
}

function aperturaCassetto() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
        data: {
            id_module: globals.vendita_easy.id_modulo_vendita,
            id_record: globals.id_record,
            op: "apertura_cassetto",
        }
    }).done(function (data) {
        swal(globals.vendita_easy.aperturaCassetto.titolo, globals.vendita_easy.aperturaCassetto.messaggio, "success");
    });
}

function aggiungiBarcode(barcode) {
    $('#mini-loader').show();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "cerca-barcode",
            barcode: barcode,
        }
    }).done(function (response) {
        if (response.id) {
            aggiungiArticolo(response.id);

            $("#barcode_reader").val("");
        } else {
            swal(globals.vendita_easy.aggiungiBarcode.titolo, globals.vendita_easy.aggiungiBarcode.messaggio, "warning");
        }
    });
}

function aggiungiArticolo(id_articolo) {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "add_articolo",
            idarticolo: id_articolo,
        }
    }).done(function (response) {
        caricaRighe();
        caricaCosti();

        $('#mini-loader').fadeOut();
    });
}

function movimenta(){
    openModal("Aggiungi...", globals.rootdir + "/add.php?id_module=49");
}