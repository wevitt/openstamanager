<?php

include_once __DIR__.'/../../core.php';

?>

<form action="" method="post" id="add-form">
  <input type="hidden" name="op" value="add">
  <input type="hidden" name="backto" value="record-edit">

  <div class="row">
    <div class="col-md-9">
      {["type":"text", "label":"<?php echo tr('Nome');?>", "name":"nome", "required":1]}
    </div>
    <div class="col-md-3">
      {["type":"select", "label":"<?php echo tr('Tipologia');?>", "name":"sezionale", "values":"list=\"vendite\":\"<?php echo tr('Vendite');?>\", \"acquisti\":\"<?php echo tr('Acquisti');?>\" ", "required":1]}
    </div>
  </div>

  <div class='pull-right'>
    <button type='submit' class='btn btn-primary'><i class='fa fa-plus'></i> <?php echo tr('Aggiungi');?></button>
  </div>

  <div class='clearfix'></div>

</form>
