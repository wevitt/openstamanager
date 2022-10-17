
/**
 * Funzione per salvare i contenuti di un form via AJAX, utilizzando una struttura più recente fondata sull'utilizzo di Promise.
 *
 * @param button
 * @param form
 * @param data
 * @returns {Promise<unknown>}
 */
function salvaForm(form, data = {}, button = null) {
    return new Promise(function (resolve, reject) {
        // Caricamento visibile nel pulsante
        let restore = buttonLoading(button);

        // Messaggio in caso di eventuali errori
        let valid = $(form).parsley().validate();
        if (!valid) {
            swal({
                type: "error",
                title: globals.translations.ajax.missing.title,
                text: globals.translations.ajax.missing.text,
            });
            buttonRestore(button, restore);

            reject();
            return;
        }

        // Gestione grafica di salvataggio
        $("#main_loading").show();
        content_was_modified = false;

        // Lettura dei contenuti degli input
        data = {...getInputsData(form), ...data};
        data.ajax = 1;

        // Fix per gli id di default
        data.id_module = data.id_module ? data.id_module : globals.id_module;
        data.id_record = data.id_record ? data.id_record : globals.id_record;
        data.id_plugin = data.id_plugin ? data.id_plugin : globals.id_plugin;

        // Invio dei dati
        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: data,
            type: "POST",
            success: function (data) {
                let response = data.trim();

                // Tentativo di conversione da JSON
                try {
                    response = JSON.parse(response);
                } catch (e) {
                }

                // Gestione grafica del successo
                $("#main_loading").fadeOut();
                renderMessages();
                buttonRestore(button, restore);

                resolve(response);
            },
            error: function (data) {
                toastr["error"](data);

                // Gestione grafica dell'errore
                $("#main_loading").fadeOut();
                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
                buttonRestore(button, restore);

                reject(data);
            }
        });
    });
}

/**
 * Funzione per recuperare come oggetto i contenuti degli input interni a un tag HTML.
 *
 * @param {HTMLElement|string|jQuery} form
 * @returns {{}}
 */
function getInputsData(form) {
    let place = $(form);
    let data = {};

    // Gestione input previsti con sistema JS integrato
    let inputs = place.find('.openstamanager-input');
    for (const x of inputs) {
        const i = input(x);
        const name = i.getElement().attr('name');
        const value = i.get();

        data[name] = value === undefined || value === null ? undefined : value;
    }

    // Gestione input HTML standard
    let standardInputs = place.find(':input').not('.openstamanager-input').serializeArray();
    for (const x of standardInputs) {
        data[x.name] = x.value;
    }

    // Gestione hash dell'URL
    let hash = window.location.hash;
    if (hash) {
        data['hash'] = hash;
    }

    return data;
}

/**
 * Modal.
 * @param title
 * @param href
 */
function openModal(title, href) {
    // Fix - Select2 does not function properly when I use it inside a Bootstrap modal.
    $.fn.modal.Constructor.prototype.enforceFocus = function () {
    };

    // Generazione dinamica modal
    do {
        id = '#bs-popup-' + Math.floor(Math.random() * 100);
    } while ($(id).length !== 0);

    if ($(id).length === 0) {
        $('#modals').append('<div class="modal fade" id="' + id.replace("#", "") + '" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>');
    }

    $(id).on('hidden.bs.modal', function () {
        if ($('.modal-backdrop').length < 1) {
            $(this).html('');
            $(this).data('modal', null);
        }
    });

    // Promise per la gestione degli eventi
    const d = $.Deferred();
    $(id).one('shown.bs.modal', d.resolve);

    const content = '<div class="modal-dialog modal-lg">\
    <div class="modal-content">\
        <div class="modal-header bg-light-blue">\
            <button type="button" class="close" data-dismiss="modal">\
                <span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span>\
            </button>\
            <h4 class="modal-title">\
                <i class="fa fa-pencil"></i> ' + title + '\
            </h4>\
        </div>\
        <div class="modal-body">|data|</div>\
    </div>\
</div>';

    // Lettura contenuto div
    if (href.substr(0, 1) === '#') {
        const data = $(href).html();

        $(id).html(content.replace("|data|", data));
        $(id).modal('show');
    } else {
        $.get(href, function (data, response) {
            if (response === 'success') {
                $(id).html(content.replace("|data|", data));
                $(id).modal('show');
            }
        });
    }

    return d.promise();
}
