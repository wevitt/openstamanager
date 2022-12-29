<?php

include_once __DIR__.'/../../core.php';

?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-7">
            {["type":"text", "label":"<?php echo tr('Nome');?>", "name":"nome", "required":1, "value":"$nome$"]}
        </div>
        <div class="col-md-3">
            {["type":"select", "label":"<?php echo tr('Tipologia');?>", "name":"sezionale", "values":"list=\"vendite\":\"<?php echo tr('Vendite');?>\", \"acquisti\":\"<?php echo tr('Acquisti');?>\" ", "value":"$sezionale$", "required":1]}
        </div>
        <div class="col-md-2">
            {[ "type": "checkbox", "label": "<?php echo tr('Attiva'); ?>", "name": "enabled", "value": "$enabled$", "help": "<?php echo tr('Attiva la sorgente dati per includerla al calcolo del budget.'); ?>", "placeholder": "<?php echo tr('Attiva'); ?>" ]}
		</div>
    </div>

    <div class="alert alert-info">
        <?php echo tr('Le sorgenti esterne sono query SQL che aggiungono informazioni previsionali'); ?>.<br>
        <?php echo tr('Ciascuna query deve ritornare almeno questi campi'); ?>:
        <ul>
            <li><b><?php echo tr('totale'); ?></b>: <?php echo tr('importo da conteggiare nel periodo scelto'); ?></li>
            <li><b><?php echo tr('descrizione'); ?></b>: <?php echo tr('descrizione da visualizzare nei dettagli'); ?></li>
            <li><b><?php echo tr('data'); ?></b>: <?php echo tr('data in cui visualizzare la previsione'); ?></li>
            <li><b><?php echo tr('anagrafica'); ?></b>: <?php echo tr('anagrafica collegata alla previsione'); ?></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            {["type":"textarea", "label":"<?php echo tr('Query sezione economica');?>", "name":"query", "value":"$query$", "help":"<?php echo tr('Operazione che verrà eseguita nella parte dell\'andamento economico');?>"]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {["type":"textarea", "label":"<?php echo tr('Query sezione finanziaria');?>", "name":"query2", "value":"$query2$", "help":"<?php echo tr('Operazione che verrà eseguita nella parte dell\'andamento finanziario');?>"]}
        </div>
    </div>

</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>