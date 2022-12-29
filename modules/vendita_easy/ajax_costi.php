<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

echo '
<table class="table table-striped table-hover table-condensed table-total" >    
    <tr>
        <th>'.tr('Sconto').':</th>
        <th class="text-right">'.moneyFormat($documento->sconto, 2).'</th>
        <th>'.tr('Subtotale').':</th>
        <th class="text-right">'.moneyFormat($documento->totale_imponibile, 2).'</th>
    </tr>

    <tr>
        <th>'.tr('IVA').':</th>
        <th class="text-right">'.moneyFormat($documento->iva, 2).'</th>
        <th>'.tr('Totale').':</th>
        <th class="text-right" style="font-size:30px; color:red;">'.moneyFormat($documento->totale, 2).'</th>
    </tr>
</table>';
