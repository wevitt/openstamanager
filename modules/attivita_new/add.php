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

use Modules\Attivita\Intervento;

include_once __DIR__.'/../../core.php';

// Lettura dei parametri di interesse
$id_anagrafica = filter('idanagrafica');
$id_sede = filter('idsede');
$richiesta = filter('richiesta');
$id_tipo = filter('id_tipo');

$origine_dashboard = get('ref') !== null;
$module_anagrafiche = Modules::get('Anagrafiche');
$id_plugin_sedi = Plugins::get('Sedi')['id'];

// Calcolo dell'orario di inizio e di fine sulla base delle informazioni fornite
$orario_inizio = filter('orario_inizio');
$orario_fine = filter('orario_fine');
if (null == $orario_inizio || '00:00:00' == $orario_inizio) {
    $orario_inizio = date('H').':00:00';
    $orario_fine = date('H').':00:00';
}

// Un utente del gruppo Tecnici può aprire attività solo a proprio nome
$id_tecnico = filter('id_tecnico');
if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
    $id_tecnico = $user['idanagrafica'];
} elseif ($user['gruppo'] == 'Clienti' && !empty($user['idanagrafica'])) {
    $id_cliente = $user['idanagrafica'];
}

// Stato di default associato all'attivitò
$stato = $dbo->fetchArray("SELECT * FROM in_statiintervento WHERE codice = 'WIP'");
$id_stato = $stato['idstatointervento'];

// Se è indicata un'anagrafica relativa, si carica il tipo di intervento di default impostato
if (!empty($id_anagrafica)) {
    $anagrafica = $dbo->fetchOne('SELECT idtipointervento_default, idzona FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica));
    $id_tipo = $id_tipo ?: $anagrafica['idtipointervento_default'];
    $id_zona = $anagrafica['idzona'];
}

// Gestione dell'impostazione dei Contratti
$id_intervento = filter('id_intervento');
$id_contratto = filter('idcontratto');
$id_promemoria_contratto = filter('idcontratto_riga');
$id_ordine = null;

// Trasformazione di un Promemoria dei Contratti in Intervento
if (!empty($id_contratto) && !empty($id_promemoria_contratto)) {
    $contratto = $dbo->fetchOne('SELECT *, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS idzona FROM co_contratti WHERE id = '.prepare($id_contratto));
    $id_anagrafica = $contratto['idanagrafica'];
    $id_zona = $contratto['idzona'];

    // Informazioni del Promemoria
    $promemoria = $dbo->fetchOne('SELECT *, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = co_promemoria.idtipointervento) AS tempo_standard FROM co_promemoria WHERE idcontratto='.prepare($id_contratto).' AND id = '.prepare($id_promemoria_contratto));
    $id_tipo = $promemoria['idtipointervento'];
    $data = (null !== filter('data')) ? filter('data') : $promemoria['data_richiesta'];
    $richiesta = $promemoria['richiesta'];
    $id_sede = $promemoria['idsede'];
    $impianti_collegati = $promemoria['idimpianti'];

    // Generazione dell'orario di fine sulla base del tempo standard definito dal Promemoria
    if (!empty($promemoria['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $promemoria['tempo_standard']));
    }

    // Caricamento degli impianti a Contratto se non definiti in Promemoria
    if (empty($impianti_collegati)) {
        $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto = '.prepare($id_contratto));
        $impianti_collegati = implode(',', array_column($rs, 'idimpianto'));
    }
}

// Gestione dell'aggiunta di una sessione a un Intervento senza sessioni (Promemoria intervento) da Dashboard
elseif (!empty($id_intervento)) {
    $intervento = $dbo->fetchOne('SELECT *, (SELECT idcontratto FROM co_promemoria WHERE idintervento = in_interventi.id LIMIT 0,1) AS idcontratto, in_interventi.id_preventivo as idpreventivo, (SELECT tempo_standard FROM in_tipiintervento WHERE idtipointervento = in_interventi.idtipointervento) AS tempo_standard FROM in_interventi WHERE id = '.prepare($id_intervento));

    $id_tipo = $intervento['idtipointervento'];
    $data = (null !== filter('data')) ? filter('data') : $intervento['data_richiesta'];
    $data_richiesta = $intervento['data_richiesta'];
    $data_scadenza = $intervento['data_scadenza'];
    $richiesta = $intervento['richiesta'];
    $id_sede = $intervento['idsede'];
    $id_anagrafica = $intervento['idanagrafica'];
    $id_cliente_finale = $intervento['idclientefinale'];
    $id_stato = $intervento['idstatointervento'];
    $id_contratto = $intervento['idcontratto'];
    $id_preventivo = $intervento['idpreventivo'];
    $id_zona = $intervento['idzona'];

    // Generazione dell'orario di fine sulla base del tempo standard definito dall'Intervento
    if (!empty($intervento['tempo_standard'])) {
        $orario_fine = date('H:i:s', strtotime($orario_inizio) + ((60 * 60) * $intervento['tempo_standard']));
    }

    $rs = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento = '.prepare($id_intervento));
    $impianti_collegati = implode(',', array_column($rs, 'idimpianto'));
}

// Selezione dei tecnici assegnati agli impianti selezionati
if (!empty($impianti_collegati)) {
    $tecnici_impianti = $dbo->fetchArray('SELECT idtecnico FROM my_impianti WHERE id IN ('.prepare($impianti_collegati).')');
    $id_tecnico = array_unique(array_column($tecnici_impianti, 'idtecnico'));
}

// Impostazione della data se mancante
if (empty($data)) {
    $data = filter('data');
    if (null == $data) {
        $data = date('Y-m-d');
    }
}

// Impostazione della data di fine da Dashboard
if (empty($data_fine)) {
    $data_fine = filter('data_fine');
    if (null == $data_fine) {
        $data_fine = $data;
    }
}
$data_fine = $data_fine ?: $data;

$inizio_sessione = $data.' '.$orario_inizio;
$fine_sessione = $data_fine.' '.$orario_fine;

// Calcolo del nuovo codice
$new_codice = Intervento::getNextCodice($data);

$url = $module->fileurl('modals/dettaglio_utente.php');

$_SESSION['current_tipo_intervento'][""] = 0;
?>

<style>
    .modal-dettaglio-utente {
        display: none;
    }

    @media only screen and (max-width: 1199px) {
        .modal-dettaglio-utente {
            display: block;
        }

        .div-dettaglio-utente {
            display: none;
        }
    }
</style>

<form action="" method="post" id="add-form" class="row">
    <input type="hidden" class="url" value="<?= $url ?>">
    <input type="hidden" class="base_path" value="<?= base_path() ?>">
    <input type="hidden" name="idstatointervento" value="8">

	<input type="hidden" name="op" value="add">
	<input type="hidden" name="ref" value="<?= get('ref') ?>">
	<input type="hidden" name="backto" value="record-edit">
    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">
    <?php if (!empty($id_promemoria_contratto)) { ?>
        <input type="hidden" name="idcontratto_riga" value="<?= $id_promemoria_contratto ?>">
    <?php } ?>
    <?php if (!empty($id_intervento)) { ?>
        <input type="hidden" name="id_intervento" value="<?= $id_intervento ?>">
    <?php } ?>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-4">
                <label for="cliente" class="card-header">
                    <span class="d-inline-block"><?= tr('Cliente') ?></span>
                    <span style="padding:0px" class="d-inline-block btn float-right">
                        <a class="btn modal-dettaglio-utente" style="padding:0px"><i class="fa fa-info-circle"></i></a>
                    </span>
                </label>
                {[ "type": "select", "id": "cliente", "name": "idanagrafica", "required": 1, "value": "<?= (!$id_cliente ? $id_anagrafica : $id_cliente) ?>", "ajax-source": "clienti", "icon-after": "add|<?= $module_anagrafiche['id'] ?>|tipoanagrafica=Cliente&readonly_tipo=1", "readonly": "<?= ((empty($id_anagrafica) && empty($id_cliente)) ? 0 : 1) ?>" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "select", "label": "<?= tr('Sede destinazione') ?>", "name": "idsede_destinazione", "value": "<?= $id_sede ?>", "ajax-source": "sedi", "select-options": <?= json_encode(['idanagrafica' => $id_anagrafica]) ?>, "icon-after": "add|<?= $module_anagrafiche['id'] ?>|id_plugin=<?= $id_plugin_sedi ?>&id_parent=<?= $id_anagrafica ?>" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "select", "label": "<?= tr('Per conto di') ?>", "name": "idclientefinale", "value": "<?= $id_cliente_finale ?>", "ajax-source": "clienti" ]}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                {[ "type": "select", "label": "<?= tr('Referente') ?>", "name": "idreferente", "ajax-source": "referenti", "select-options": <?= json_encode(['idanagrafica' => $id_anagrafica, 'idclientefinale' => $id_cliente_finale]) ?>, "icon-after": "add|<?= Modules::get('Anagrafiche')['id'] ?>|id_plugin=<?= Plugins::get('Referenti')['id'] ?>&id_parent=<?= $id_anagrafica ?>" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "select", "label": "<?= tr('Tipo') ?>", "name": "idtipointervento", "required": 1, "value": "<?= $id_tipo ?>", "ajax-source": "tipiintervento" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "timestamp", "label": "<?= tr('Data/ora richiesta') ?>", "name": "data_richiesta", "required": 1, "value": "<?= ($data_richiesta ?: '-now-') ?>" ]}
            </div>
        </div>

        <div class="row" style="margin-top:10px">
            <div class="col-md-12">
                {[ "type": "select", "label": "<?= tr('Assegnati') ?>", "multiple": "1", "name": "assegnati[]", "ajax-source": "tipo_assegnati", "value": "", "icon-after": "add|<?= $module_anagrafiche['id'] ?>|tipoanagrafica=Tecnico&readonly_tipo=1" ]}
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group">
                            <button type="button" class="btn btn-xs btn-primary" onclick="assegnaTutti()">
                                <?= tr('Tutti') ?>
                            </button>

                            <button type="button" class="btn btn-xs btn-danger" onclick="deassegnaTutti()">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="row">
            <div class="col-md-12">
                {[ "type": "ckeditor", "label": "<?= tr('Richiesta') ?>", "name": "richiesta", "id": "richiesta_add", "required": 1, "value": "<?= $richiesta ?>", "extra": "style='max-height:80px;'" ]}
            </div>
        </div>


        <?php
            $espandi_dettagli = setting('Espandi automaticamente la sezione "Dettagli aggiuntivi"');
        ?>
        <!-- DATI AGGIUNTIVI -->
        <div class="box box-info collapsable <?= (empty($espandi_dettagli) ? 'collapsed-box' : '') ?>">
            <div class="box-header with-border">
                <h3 class="box-title"><?= tr('Dettagli aggiuntivi') ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-<?= (empty($espandi_dettagli) ? 'plus' : 'minus') ?>"></i>
                    </button>
                </div>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "<?= tr('Data/ora scadenza') ?>", "name": "data_scadenza", "required": 0, "value": "<?= $data_scadenza ?>" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "select", "label": "<?= tr('Impianto') ?>", "multiple": 1, "name": "idimpianti[]", "value": "<?= $impianti_collegati ?>", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": <?= ($id_anagrafica ?: '""') ?>, "idsede_destinazione": <?= ($id_sede ?: '""') ?>}, "icon-after": "add|<?= Modules::get('Impianti')['id'] ?>|id_anagrafica=<?= $id_anagrafica ?>" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "select", "label": "<?= tr('Componenti') ?>", "multiple": 1, "name": "componenti[]", "placeholder": "<?= tr('Seleziona prima un impianto') ?>", "ajax-source": "componenti" ]}
                    </div>
                </div>
            </div>
        </div>

        <!-- ORE LAVORO -->
        <div class="box box-info collapsable <?= ($origine_dashboard ? '' : 'collapsed-box') ?>">
            <div class="box-header with-border">
                <h3 class="box-title"><?= tr('Ore lavoro') ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-<?= ($origine_dashboard ? 'minus' : 'plus') ?>"></i>
                    </button>
                </div>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "<?= tr('Inizio attività') ?>", "name": "orario_inizio", "required": <?= ($origine_dashboard ? 1 : 0) ?>, "value": "<?= $inizio_sessione ?>" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "<?= tr('Fine attività') ?>", "name": "orario_fine", "required": <?= ($origine_dashboard ? 1 : 0) ?>, "value": "<?= $fine_sessione ?>" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "select", "label": "<?= tr('Zona') ?>", "name": "idzona", "values": "query=SELECT id, CONCAT_WS(' - ', nome, descrizione) AS descrizione FROM an_zone ORDER BY nome", "placeholder": "<?= tr('Nessuna zona') ?>", "help": "<?= tr('La zona viene definita automaticamente in base al cliente selezionato') ?>", "readonly": "1", "value": "<?= $id_zona ?>" ]}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        {[ "type": "select", "label": "<?= tr('Tecnici') ?>", "multiple": "1", "name": "idtecnico[]", "required": <?= ($origine_dashboard ? 1 : 0) ?>, "ajax-source": "tecnici", "value": "<?= $id_tecnico ?>", "icon-after": "add|<?= $module_anagrafiche['id'] ?>|tipoanagrafica=Tecnico&readonly_tipo=1||<?= (empty($id_tecnico) ? '' : 'disabled') ?>" ]}
                    </div>
                </div>

                <div id="info-conflitti-add"></div>

            </div>
        </div>

        <!-- RICORRENZA -->
        <div class="box box-info collapsable collapsed-box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= tr('Ricorrenza') ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        {[ "type": "checkbox", "label": "<?= tr('Attività ricorrente') ?>", "name": "ricorsiva", "value": "" ]}
                    </div>

                    <div class="col-md-4 ricorrenza">
                        {[ "type": "timestamp", "label": "<?= tr('Data/ora inizio') ?>", "name": "data_inizio_ricorrenza", "value": "<?= ($data_richiesta ?: '-now-') ?>" ]}
                    </div>

                    <div class="col-md-4 ricorrenza">
                        {[ "type": "number", "label": "<?= tr('Periodicità') ?>", "name": "periodicita", "decimals": "0", "icon-after": "choice|period|months", "value": "1" ]}
                    </div>
                </div>

                <div class="row ricorrenza">
                    <div class="col-md-4">
                        {[ "type": "select", "label": "<?= tr('Metodo fine ricorrenza') ?>", "name": "metodo_ricorrenza", "values": "list=\"data\":\"Data fine\",\"numero\":\"Numero ricorrenze\"" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "<?= tr('Data/ora fine') ?>", "name": "data_fine_ricorrenza" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "number", "label": "<?= tr('Numero ricorrenze') ?>", "name": "numero_ricorrenze", "decimals": "0" ]}
                    </div>
                </div>

                <div class="row ricorrenza">
                    <div class="col-md-4">
                        {[ "type": "select", "label": "<?= tr('Stato ricorrenze') ?>", "name": "idstatoricorrenze", "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL AND is_completato=0 ORDER BY descrizione" ]}
                    </div>

                    <div class="col-md-4">
                        {[ "type": "checkbox", "label": "<?= tr('Riporta sessioni di lavoro') ?>", "name": "riporta_sessioni", "value": "" ]}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DETTAGLI CLIENTE -->
    <div class="col-lg-4 div-dettaglio-utente" style="padding-right:30px; font-size:10px;">
        <div class="">
            <h3><?= tr('Dettagli cliente') ?></h3>
        </div>

        <div class="box-body" id="dettagli_cliente">
            <?= tr('Seleziona prima un cliente') ?>...
        </div>
    </div>


	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right" style="padding-right:30px;">
			<button type="button" class="btn btn-primary" onclick="salva(this)">
                <i class="fa fa-plus"></i> <?= tr('Aggiungi') ?>
            </button>
		</div>
	</div>
</form>

<?php
if (!empty($id_intervento)) {
    echo '
    <script type="text/javascript">
            $(document).ready(function() {
            input("idsede_destinazione").disable();
            input("idpreventivo").disable();
            input("idcontratto").disable();
            input("idordine").disable();
            input("idreferente").disable();
            input("idimpianti").disable();
            input("componenti").disable();
            input("idanagrafica").disable();
            input("idclientefinale").disable();
            input("idzona").disable();
            input("idtipointervento").disable();
            input("idstatointervento").disable();
            input("data_richiesta").disable();
        });
    </script>';
}

// Disabilito i campi che non devono essere modificati per poter collegare l'Intervento al Promemoria del Contratto
if (!empty($id_contratto) && !empty($id_promemoria_contratto)) {
    echo '
    <script type="text/javascript">
        $(document).ready(function() {
            input("idanagrafica").disable();
            input("idclientefinale").disable();
            input("idzona").disable();
            input("idtipointervento").disable();
        });
    </script>';
}

echo '
<script type="text/javascript">
    var anagrafica = input("idanagrafica");
    var sede = input("idsede_destinazione");
    var contratto = input("idcontratto");
    var preventivo = input("idpreventivo");
    var ordine = input("idordine");
    var referente = input("idreferente");
    var cliente_finale = input("idclientefinale");

	$(document).ready(function() {
        if(!anagrafica.get()){
           sede.disable();
           preventivo.disable();
           contratto.disable();
           ordine.disable();
           referente.disable();
           input("idimpianti").disable();
           input("componenti").disable();
        } else{
           let value = anagrafica.get();
           updateSelectOption("idanagrafica", value);
           session_set("superselect,idanagrafica",value, 0);
        }

		// Quando modifico orario inizio, allineo anche l\'orario fine
		let orario_inizio = input("orario_inizio").getElement();
		let orario_fine = input("orario_fine").getElement();
        orario_inizio.on("dp.change", function (e) {
            orario_fine.data("DateTimePicker").minDate(e.date);
            orario_fine.change();
        });

        // Refresh modulo dopo la chiusura di una pianificazione attività derivante dalle attività
        // da pianificare, altrimenti il promemoria non si vede più nella lista a destra
		// TODO: da gestire via ajax
        if($("input[name=idcontratto_riga]").val()) {
            $("#modals > div button.close").on("click", function() {
                location.reload();
            });
        }

        // Ricorrenza
        $(".ricorrenza").addClass("hidden");
    });

	input("idtecnico").change(function() {
	    calcolaConflittiTecnici();
	});

    // Gestione della modifica dell\'anagrafica
	anagrafica.change(function() {
        let value = $(this).val();
        updateSelectOption("idanagrafica", value);
        session_set("superselect,idanagrafica",value, 0);

        let selected = !$(this).val();
        let placeholder = selected ? "'.tr('Seleziona prima un cliente').'" : "'.tr("Seleziona un'opzione").'";

        sede.setDisabled(selected)
            .getElement().selectReset(placeholder);

        preventivo.setDisabled(selected)
            .getElement().selectReset(placeholder);

        contratto.setDisabled(selected)
            .getElement().selectReset(placeholder);

        ordine.setDisabled(selected)
            .getElement().selectReset(placeholder);

        referente.setDisabled(selected)
            .getElement().selectReset(placeholder);

        input("idimpianti").setDisabled(selected);

        let data = anagrafica.getData();
		if (data) {
		    input("idzona").set(data.idzona ? data.idzona : "");
			// session_set("superselect,idzona", $(this).selectData().idzona, 0);

            // Impostazione del tipo intervento da anagrafica
            input("idtipointervento").getElement()
                .selectSetNew(data.idtipointervento, data.idtipointervento_descrizione);

            // Impostazione del contratto predefinito da anagrafica
            if(data.id_contratto) {
                input("idcontratto").getElement()
                    .selectSetNew(data.id_contratto, data.descrizione_contratto);
            }
		}

        if (data !== undefined) {
            // Carico nel panel i dettagli del cliente
            $.get("'.base_path().'/ajax_complete.php?module=Interventi&op=dettagli&id_anagrafica=" + value, function(data){
                $("#dettagli_cliente").html(data);
            });
        } else {
            $("#dettagli_cliente").html("'.tr('Seleziona prima un cliente').'...");
        }

        plus_sede = $(".modal #idsede_destinazione").parent().find(".btn");
        plus_sede.attr("onclick", plus_sede.attr("onclick").replace(/id_parent=[0-9]*/, "id_parent=" + value));

        plus_impianto = $(".modal #idimpianti").parent().find(".btn");
        plus_impianto.attr("onclick", plus_impianto.attr("onclick").replace(/id_anagrafica=[0-9]*/, "id_anagrafica=" + value));

        plus_contratto = $(".modal #idcontratto").parent().find(".btn");
        plus_contratto.attr("onclick", plus_contratto.attr("onclick").replace(/idanagrafica=[0-9]*/, "idanagrafica=" + value));

        plus_referente = $(".modal #idreferente").parent().find(".btn");
        plus_referente.attr("onclick", plus_referente.attr("onclick").replace(/id_parent=[0-9]*/, "id_parent=" + value));
	});

    //gestione del cliente finale
    cliente_finale.change(function() {
        updateSelectOption("idclientefinale", $(this).val());
        session_set("superselect,idclientefinale", $(this).val(), 0);

        referente.getElement()
            .selectReset("'.tr("Seleziona un'opzione").'");
    });

    // Gestione della modifica della sede selezionato
	sede.change(function() {
        updateSelectOption("idsede_destinazione", $(this).val());
		session_set("superselect,idsede_destinazione", $(this).val(), 0);
        input("idimpianti").getElement().selectReset();

        let data = sede.getData();
		if (data) {
		    input("idzona").set(data.idzona ? data.idzona : "");
			// session_set("superselect,idzona", $(this).selectData().idzona, 0);
		}
	});

    // Gestione della modifica dell\'ordine selezionato
	ordine.change(function() {
		if (ordine.get()) {
            contratto.getElement().selectReset();
            preventivo.getElement().selectReset();
        }
	});

    // Gestione della modifica del preventivo selezionato
	preventivo.change(function() {
		if (preventivo.get()){
            contratto.getElement().selectReset();
            ordine.getElement().selectReset();

            input("idtipointervento").getElement()
                .selectSetNew($(this).selectData().idtipointervento, $(this).selectData().idtipointervento_descrizione);
        }
	});

    // Gestione della modifica del contratto selezionato
	contratto.change(function() {
		if (contratto.get()){
            preventivo.getElement().selectReset();
            ordine.getElement().selectReset();

            $("input[name=idcontratto_riga]").val("");
        }
	});

    // Gestione delle modifiche agli impianti selezionati
	input("idimpianti").change(function() {
        updateSelectOption("matricola", $(this).val());
		session_set("superselect,matricola", $(this).val(), 0);

        input("componenti").setDisabled(!$(this).val())
            .getElement().selectReset();
	});

    // Automatismo del tempo standard
    input("idtipointervento").change(function() {
        let data = $("#idtipointervento").selectData();
        if (data && data.tempo_standard > 0) {
            let orario_inizio = input("orario_inizio").get();
            let tempo_standard = data.tempo_standard * 60;
            let nuovo_orario_fine = moment(orario_inizio, "DD/MM/YYYY HH:mm").add(tempo_standard, "m").format("DD/MM/YYYY HH:mm");
            input("orario_fine").set(nuovo_orario_fine);
        }

        session_set("current_tipo_intervento", $("[name=\'idtipointervento\']").val(), 0);
        assegnaTutti();
    });';

        if (!$origine_dashboard) {
            echo '
	input("idtecnico").change(function() {
	    var value = $(this).val() > 0 ? true : false;
	    input("orario_inizio").setRequired(value);
	    input("orario_fine").setRequired(value);
	    input("data").setRequired(value);
	});';
        }

        echo '
	var ref = "'.get('ref').'";

	async function salva(button) {
	    // Submit attraverso ricaricamento della pagina
	    if (!ref) {
            $("#add-form").submit();
            return;
	    }

	    // Submit dinamico tramite AJAX
        let response = await salvaForm("#add-form", {
            id_module: "'.$id_module.'", // Fix creazione da Dashboard
        }, button);

        // Se l\'aggiunta intervento proviene dalla scheda di pianificazione ordini di servizio della dashboard, la ricarico
        if (ref == "dashboard") {
            $("#modals > div").modal("hide");

            // Aggiornamento elenco interventi da pianificare
            $("#calendar").fullCalendar("refetchEvents");
            $("#calendar").fullCalendar("render");
        }

        // Se l\'aggiunta intervento proviene dai contratti, faccio il submit via ajax e ricarico la tabella dei contratti
        else if (ref == "interventi_contratti") {
            $("#modals > div").modal("hide");
            parent.window.location.reload();
            //TODO: da gestire via ajax
            //$("#elenco_interventi > tbody").load(globals.rootdir + "/modules/contratti/plugins/contratti.pianificazioneinterventi.php?op=get_interventi_pianificati&idcontratto='.$id_contratto.'");
        }
    }

    function calcolaConflittiTecnici() {
        let tecnici = input("idtecnico").get();

        return $("#info-conflitti-add").load("'.$module->fileurl('occupazione_tecnici.php').'", {
            "id_module": globals.id_module,
            "tecnici[]": tecnici,
            "inizio": input("orario_inizio").get(),
            "fine": input("orario_fine").get(),
        });
    }

    function assegnaTutti() {
        deassegnaTutti();

        $.getJSON(globals.rootdir + "/ajax_select.php?op=assegnati", function(response) {
            let input_tecnici = input("assegnati").getElement();

            $.each(response.results, function(key, result) {
                input_tecnici.append(`<option value="` + result["id"] + `">` + result["descrizione"] + `</option>`);

                input_tecnici.find("option").prop("selected", true);
            });

            $("#assegnati").trigger("change");
        });
    }

    function deassegnaTutti() {
        input("assegnati").getElement().selectReset();
    }

    $("#ricorsiva").on("change", function(){
        if ($(this).is(":checked")) {
            $(".ricorrenza").removeClass("hidden");
            $("#data_inizio_ricorrenza").attr("required", true);
            $("#metodo_ricorrenza").attr("required", true);
            $("#idstatoricorrenze").attr("required", true);
        } else {
            $(".ricorrenza").addClass("hidden");
            $("#data_inizio_ricorrenza").attr("required", false);
            $("#metodo_ricorrenza").attr("required", false);
            $("#idstatoricorrenze").attr("required", false);
        }
    });

    $("#metodo_ricorrenza").on("change", function(){
        if ($(this).val()=="data") {
            input("data_fine_ricorrenza").enable();
            $("#data_fine_ricorrenza").attr("required", true);
            input("numero_ricorrenze").disable();
            input("numero_ricorrenze").set("");
        } else {
            input("numero_ricorrenze").enable();
            input("data_fine_ricorrenza").disable();
            input("data_fine_ricorrenza").set("");
            $("#data_fine_ricorrenza").attr("required", false);
        }
    });
</script>';
?>

<script>
    $(document).ready(function() {
        $('.modal-dettaglio-utente').on('click', function() {
            var url = $('.url').val();
            var base_path = $('.base_path').val();
            var dati_anagrafica = anagrafica.getData();

            if (dati_anagrafica !== undefined) {
                var data =
                    "base_path=" + base_path +
                    "&id_anagrafica=" + dati_anagrafica['id'];
                openModal(
                    "Crea percorso",
                    url + "?id_module=" + globals.id_module + "&" + data
                );
            } else {
                //coloro di rosso l'icon
            }
        })
    })
</script>
