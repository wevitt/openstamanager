<?php

include_once __DIR__.'/../../core.php';

$id_module = get('id_module');
$id_record = get('id_record');

$vendite_totali = $dbo->fetchArray("SELECT SUM( (prezzo_unitario_ivato-sconto_unitario_ivato)*qta ) AS totale, DATE_FORMAT(`data`, '%Y-%m-%d') AS `data` FROM vb_righe_venditabanco INNER JOIN vb_venditabanco ON vb_venditabanco.id=vb_righe_venditabanco.idvendita WHERE vb_venditabanco.idstato=1 AND deleted_at IS NULL AND vb_venditabanco.id NOT IN (SELECT vb_venditabanco_movimenti.idvendita FROM vb_venditabanco_movimenti) GROUP BY DATE_FORMAT(`data`, '%Y-%m-%d') ORDER BY DATE_FORMAT(`data`, '%Y-%m-%d') ASC");

$str_vendite = '';
$tab_vendite = '
<form action="" method="post" id="form-edit-riga">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="chiusura" value="1">
    <input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="row">
        <div class="col-md-12 text-center" style="max-height:400px;overflow:auto;">
            <table class="table table-bordered table-stripped">
                <tr>
                    <th class="text-center" width="5%">#</th>
                    <th width="80%">'.tr('Data').'</th>
                    <th class="text-right" width="15%">'.tr('Totale').'</th>
                </tr>';

                foreach ($vendite_totali as $vendita) {
                    $lista_vendite = $dbo->fetchArray("SELECT vb_venditabanco.*, SUM( (prezzo_unitario_ivato-sconto_unitario_ivato)*qta ) AS totale FROM vb_righe_venditabanco INNER JOIN vb_venditabanco ON vb_venditabanco.id=vb_righe_venditabanco.idvendita WHERE DATE_FORMAT(`data`, '%Y-%m-%d')=".prepare($vendita['data']).' AND vb_venditabanco.id NOT IN (SELECT vb_venditabanco_movimenti.idvendita FROM vb_venditabanco_movimenti) GROUP BY vb_venditabanco.id ');

                    if ($vendita['data'] < date('Y-m-d')) {
                        $colore = 'style="background-color:#fc8484;"';
                    }

                    $dettaglio_vendite = '
                    <tr class="" id="dettaglio_'.$vendita['data'].'" hidden>
                        <td colspan="3">
                            <table class="table table-bordered table-stripped">
                            <tr>
                                <th>'.tr('Vendita').'</th>
                                <th class="text-right">'.tr('Totale').'</th>
                            </tr>';

                    foreach ($lista_vendite as $dettaglio) {
                        $dettaglio_vendite .= '
                                <tr>
                                    <td>
                                        '.tr('Vendita al banco num. _NUMERO_ del _DATA_', [
                                            '_NUMERO_' => $dettaglio['numero_esterno'],
                                            '_DATA_' => Translator::dateToLocale($dettaglio['data']),
                                        ]).'
                                    </td>
                                    <td class="text-right">
                                        '.moneyFormat($dettaglio['totale']).'
                                    </td>
                                </tr>';
                    }
                    $dettaglio_vendite .= '
                            </td>
                        </table>
                    </tr>';

                    $str_vendite .= '
                    <tr '.$colore.'>
                        <td class="text-center">
                            <a class="btn btn-primary btn-sm" onclick="if($(\'#dettaglio_'.$vendita['data'].'\').is(\':visible\')){$(\'#dettaglio_'.$vendita['data'].'\').hide();}else{$(\'#dettaglio_'.$vendita['data'].'\').show();}"><i class="fa fa-plus"></i></a>
                        </td>
                        <td>'.Translator::dateToLocale($vendita['data']).'</td>
                        <td class="text-right">'.moneyFormat($vendita['totale']).'</td>
                    </tr>'.$dettaglio_vendite;
                }
            $tab_vendite .= $str_vendite.'
            </table>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-lg btn-success">'.tr('Chiudi').'</button>
        </div>
    </div>
</form>';

echo $tab_vendite;
