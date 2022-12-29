<?php

include_once __DIR__.'/../../core.php';

$previsioni = $dbo->fetchArray("
    SELECT 
        bu_previsionale.descrizione,
        bu_previsionale.id_conto,
        bu_previsionale.codice,
        bu_previsionale.id_anagrafica,
        bu_previsionale.totale,
        bu_previsionale.sezione,
        GROUP_CONCAT( DATE_FORMAT(bu_previsionale.data, '%Y-%m')) AS `data`
    FROM 
        bu_previsionale 
    WHERE 
        `id_movimento` IS NULL 
        AND 
        (`data` BETWEEN ".prepare($_SESSION['period_start'])." AND ".prepare($_SESSION['period_end']).")
    GROUP BY 
        bu_previsionale.codice
    ORDER BY 
        descrizione ASC
");
    

if (!empty($previsioni)) {
    echo '
    <table class="table table-bordered table-striped table-condensed" style="table-layout:fixed;">
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="300">'.tr('Conto').'</th>
            <th width="300">'.tr('Anagrafica').'</th>
            <th width="250">'.tr('Sezione').'</th>
            <th width="150">'.tr('Importo').'</th>
            <th width="80"></th>
        </tr>';
    for ($i=0; $i<count($previsioni); $i++) {
        echo '
            <tr>
                <td>
                    {["type":"text", "name":"descrizione['.$id.']", "value":"'.$previsioni[$i]['descrizione'].'", "required":1 ]}
                </td>
                <td>
                    {[ "type": "select", "name": "id_conto['.$id.']", "value": "'.$previsioni[$i]['id_conto'].'", "ajax-source": "conti_budget" ]}
                </td>
                <td>
                    {[ "type": "select", "name": "id_anagrafica['.$id.']", "value": "'.$previsioni[$i]['id_anagrafica'].'", "ajax-source": "anagrafiche" ]}
                </td>
                <td>
                {[ "type": "select", "name": "sezione['.$id.']", "values": "list=\"economico_finanziario\":\"Economico + Finanziario\", \"economico\":\"Solo economico\", \"finanziario\":\"Solo finanziario\"", "value": "'.$previsioni[$i]['sezione'].'", "required":1 ]}
                </td>
                <td>
                    {["type":"number", "name":"importo['.$id.']", "value":"'.$previsioni[$i]['totale'].'", "required":1 ]}
                </td>
                <td class="text-center">
                    <div class="btn-group">
                        <a type="button" class="btn btn-sm btn-info" id="infobtn_'.$previsioni[$i]['codice'].'" onclick="mostra_ricorrenze('.$previsioni[$i]['codice'].');" ><i class="fa fa-retweet"></i></a>
                        <a type="button" class="btn btn-sm btn-danger" id="delbtn_'.$previsioni[$i]['icodiced'].'" onclick="elimina_previsione('.$previsioni[$i]['codice'].');"><i class="fa fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <tr class="riga_ricorrenze" id="ricorrenze_'.$previsioni[$i]['codice'].'">
                <td colspan="5">
                    <label>'.tr('Ricorrenza').':</label>
                    <br>';
        $mesi = get_mesi_ricorrenze();
        foreach ($mesi as $mese) {

                        //Blocco i mesi già trascorsi
            $data = ($mese['anno'].'-'.$mese['mese']);
                        
            echo '
                        <input type="checkbox" '.$disabled.' value="'.($mese['anno'].'-'.$mese['mese']).'" name="ricorrenza['.$i.'][]" '.(strstr($previsioni[$i]['data'], $data) ? 'checked' : '').'>'.$mese['descrizione'].'&emsp;';
        }
        echo '
                </td>
            </tr>';
    }
    echo '
    </table>';
}

?>

<script>

    $(document).ready(function(){
        init();
        $(".riga_ricorrenze").each(function(){
            $(this).hide();
        })
    });

    function elimina_previsione(codice){
        if( confirm('<?php echo tr('Eliminare questa previsione e tutte le sue ricorrenze??');?>') ){
            var data = {codice:codice, op:'delete_previsione'};
            $.ajax({
                url: globals.rootdir + "/actions.php?id_module=" + globals.id_module ,
                type: "POST",
                data: data,
                success: function(data) {
                    $('.alert-success.push').html('<i class="fa fa-check"></i> <?php echo tr('Previsione eliminata!');?>')
                    $("#previsioni").load(globals.rootdir + "/modules/previsionale/ajax_previsioni.php");
                }
            });
        }
    }

    function mostra_ricorrenze(codice){
        if( $("#ricorrenze_"+codice).is(":visible") ){
            $("#ricorrenze_"+codice).hide();
        }else{
            $("#ricorrenze_"+codice).show();
        }
    }


</script>