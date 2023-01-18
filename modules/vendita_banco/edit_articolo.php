<?php

include_once __DIR__.'/../../core.php';

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
$id_anagrafica = $options['idanagrafica'];
$id_articolo = $riga->idarticolo;
$direzione = $options['dir'];

$articolo = $dbo->fetchOne(
    'SELECT mg_articoli.*,
    IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
    IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
    IFNULL(mg_fornitore_articolo.prezzo_acquisto, mg_articoli.prezzo_acquisto) AS prezzo_acquisto,
    mg_articoli.'.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_vendita,
    mg_articoli.prezzo_vendita_ivato AS prezzo_vendita_ivato,
    IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima,
    mg_fornitore_articolo.id AS id_dettaglio_fornitore
    FROM mg_articoli
    LEFT JOIN mg_fornitore_articolo
        ON mg_fornitore_articolo.id_articolo = mg_articoli.id
        AND mg_fornitore_articolo.deleted_at IS NULL
        AND mg_fornitore_articolo.id_fornitore = '.$id_anagrafica.'
    WHERE mg_articoli.id='.prepare($id_articolo)
);

// Prezzi netti clienti / listino fornitore
/*$prezzi = $database->fetchArray(
    'SELECT minimo, massimo, sconto_percentuale, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
    FROM mg_prezzi_articoli
    WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($direzione).'AND id_anagrafica = '.prepare($id_anagrafica).'
    ORDER BY minimo ASC, massimo DESC'
);*/

// Prezzi listini clienti
$listino = $database->fetchArray(
    'SELECT sconto_percentuale AS sconto_percentuale_listino, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino
    FROM mg_listini
    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
    LEFT JOIN an_anagrafiche ON mg_listini.id=an_anagrafiche.id_listino
    WHERE mg_listini.data_attivazione<=NOW()
    AND mg_listini_articoli.data_scadenza>=NOW()
    AND mg_listini.attivo=1 AND id_articolo = '.prepare($id_articolo).'
    AND dir = '.prepare($direzione).' AND idanagrafica = '.prepare($id_anagrafica)
);

// Prezzi listini clienti sempre visibili
/*$listini_sempre_visibili = $database->fetchArray(
    'SELECT mg_listini.nome, sconto_percentuale AS sconto_percentuale_listino_visibile,
    '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino_visibile
    FROM mg_listini
    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
    WHERE mg_listini.data_attivazione<=NOW()
    AND mg_listini_articoli.data_scadenza>=NOW()
    AND mg_listini.attivo=1
    AND mg_listini.is_sempre_visibile=1
    AND id_articolo = '.prepare($id_articolo).'
    AND dir = '.prepare($direzione)
);*/

// Prezzi scheda articolo
if ($direzione == 'uscita') {
    $prezzo_articolo = $database->fetchArray(
        'SELECT prezzo_acquisto AS prezzo_scheda FROM mg_articoli WHERE id = '.prepare($id_articolo)
    );
} else {
    $prezzo_articolo = $database->fetchArray(
        'SELECT '.($prezzi_ivati ? 'prezzo_vendita_ivato' : 'prezzo_vendita').' AS prezzo_scheda FROM mg_articoli WHERE id = '.prepare($id_articolo)
    );
}

// Ultimo prezzo al cliente
$ultimo_prezzo = $dbo->fetchArray(
    'SELECT '.($prezzi_ivati ? '(prezzo_unitario_ivato-sconto_unitario_ivato)' : '(prezzo_unitario-sconto_unitario)').' AS prezzo_ultimo
    FROM co_righe_documenti LEFT JOIN co_documenti ON co_documenti.id=co_righe_documenti.iddocumento
    WHERE idarticolo='.prepare($id_articolo).'
    AND idanagrafica='.prepare($id_anagrafica).'
    AND idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir='.prepare($direzione).') ORDER BY data DESC LIMIT 0,1'
);


//$prezzo_anagrafica = getPrezzoPerQuantita(qta, tr);;
$prezzo_listino = (isset($listino[0])) ? $listino[0]['prezzo_unitario_listino'] : 0;
$prezzo_std = (isset($prezzo_articolo[0])) ? $prezzo_articolo[0]['prezzo_scheda'] : 0;
$prezzo_last = (isset($ultimo_prezzo[0])) ? $ultimo_prezzo[0]['prezzo_ultimo'] : 0;
//$prezzi_visibili = getPrezziListinoVisibili(tr);
//$prezzo_minimo = 0; //parseFloat($("#idarticolo").selectData().minimo_vendita);

echo '
<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_articolo">
    <input type="hidden" name="idriga" value="'.$riga->id.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-8">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "value": "'.$riga->descrizione.'", "required": 1]}
        </div>';

// Iva
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$riga->idiva.'", "ajax-source": "iva" ]}
        </div>
    </div>';

// Prezzo di acquisto unitario
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di acquisto').'", "name": "costo_unitario", "value": "'.$riga->costo_unitario.'", "required": 1, "icon-after": "'.currency().'" ]}
        </div>';

// Prezzo di vendita unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di vendita ivato').'", "name": "prezzo_unitario", "value": "'.$riga->prezzo_unitario_corrente.'", "required": 1, "icon-after": "'.currency().'", "help": "'.tr('Importo IVA inclusa').'" ]}';
            if ($prezzo_listino || $prezzo_std || $prezzo_last) {
                echo
                '<table class="table table-extra-condensed table-prezzi` + id_art + `" style="background:#eee; margin-top:-13px;">';

                /*if (prezzo_anagrafica) {
                    table.append(`<tr><td class="pr_anagrafica"><small>'.($options['dir'] == 'uscita' ? tr('Prezzo listino') : tr('Netto cliente')).': '.Plugins::link(($options['dir'] == 'uscita' ? 'Listino Fornitori' : 'Netto Clienti'), $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_anagrafica"><small>` + prezzo_anagrafica.toLocale() + ` ` + globals.currency + `</small></td>`);

                    let tr = table.find(".pr_anagrafica").parent();
                    if (prezzo_unitario == prezzo_anagrafica.toFixed(2)) {
                        tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
                    } else{
                        tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
                    }
                    table.append(`</tr>`);
                }*/

                if ($prezzo_listino) {
                    echo
                    '<tr>
                        <td class="pr_listino">
                            <small>'.tr('Prezzo listino').': '.Modules::link('Listini Cliente', $id_listino, tr('Visualizza'), null, '').'</small>
                        </td>
                        <td align="right" class="pr_listino">
                            <small>' . number_format($prezzo_listino, 4) . ' ' . currency() . '</small>
                        </td>';

                        if ($riga->prezzo_unitario_corrente == $prezzo_listino) {
                            echo
                            '<td>
                                <button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;">
                                    <i class="fa fa-check"></i> '.tr('Aggiorna').'
                                </button>
                            </td>';
                        } else{
                            echo
                            '<td>
                                <button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;">
                                    <i class="fa fa-refresh"></i> '.tr('Aggiorna').'
                                </button>
                            </td>';
                        }
                    echo
                    '</tr>';
                }

                if ($prezzo_std) {
                    echo
                    '<tr>
                        <td class="pr_std">
                            <small>'.tr('Prezzo articolo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small>
                        </td>
                        <td align="right" class="pr_std">
                            <small>' . number_format($prezzo_std, 4) . ' ' . currency() . '</small>
                        </td>';

                    if ($riga->prezzo_unitario_corrente == $prezzo_std) {
                        echo
                        '<td>
                            <button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;">
                                <i class="fa fa-check"></i> '.tr('Aggiorna').'
                            </button>
                        </td>';
                    } else{
                        echo
                        '<td>
                            <button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;">
                                <i class="fa fa-refresh"></i> '.tr('Aggiorna').'
                            </button>
                        </td>';
                    }
                    echo
                    '</tr>';
                }

                if ($prezzo_last) {
                    echo
                    '<tr>
                        <td class="pr_last">
                            <small>'.tr('Ultimo prezzo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small>
                        </td>
                        <td align="right" class="pr_last">
                        <small>' . number_format($prezzo_last, 4) . ' ' . currency() . '</small>
                        </td>';

                    if ($riga->prezzo_unitario_corrente == $prezzo_last) {
                        echo
                        '<td>
                            <button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;">
                                <i class="fa fa-check"></i> '.tr('Aggiorna').'
                            </button>
                        </td>';
                    } else{
                        echo
                        '<td>
                            <button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;">
                                <i class="fa fa-refresh"></i> '.tr('Aggiorna').'
                            </button>
                        </td>';
                    }
                    echo
                    '</tr>';
                }

                /*if (prezzo_minimo) {
                    table.append(`<tr><td class="pr_minimo"><small>'.tr('Prezzo minimo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_minimo"><small>` + prezzo_minimo.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

                    let tr = table.find(".pr_minimo").parent();
                    if (prezzo_unitario == prezzo_minimo.toFixed(2)) {
                        tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
                    } else{
                        tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
                    }
                    table.append(`</tr>`);
                }*/

                /*if (prezzi_visibili) {
                    let i = 0;
                    for (const prezzo_visibile of prezzi_visibili) {
                        i++;
                        let prezzo_listino_visibile = parseFloat(prezzo_visibile.prezzo_unitario_listino_visibile);
                        table.append(`<tr><td class="pr_visibile_`+ i +`"><small>'.tr('Listino visibile ').'(` + prezzo_visibile.nome + `): </small></td><td align="right" class="pr_visibile_`+ i +`"><small>` + prezzo_listino_visibile.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

                        let tr = table.find(".pr_visibile_"+ i).parent();
                        if (prezzo_unitario == prezzo_listino_visibile.toFixed(2)) {
                            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
                        } else{
                            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
                        }
                        table.append(`</tr>`);
                    }
                }*/

                echo
                    '</tbody>
                </table>';
            }
        echo
        '</div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'", "help": "'.tr('Lo sconto viene applicato sull\'imponibile. Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
		</div>
    </div>
</form>

<script>
    $(document).ready(init)

    /**
    * Funzione per aggiornare il prezzo unitario sulla base dei valori automatici.
    */
    function aggiornaPrezzoArticolo(button) {
        if ($(button).hasClass("disabled")) return;

        let prezzo = $(button).closest("tr").find("td:eq(1) small").text().split(" ")[0];
        $("#prezzo_unitario").val(prezzo);

        $(button).closest("table").find("button").removeClass("disabled");
        $(button).addClass("disabled");
    }
</script>';
