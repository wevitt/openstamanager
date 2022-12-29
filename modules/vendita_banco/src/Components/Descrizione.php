<?php

namespace Modules\VenditaBanco\Components;

use Common\Components\Description;

class Descrizione extends Description
{
    use RelationTrait;

    protected $table = 'vb_righe_venditabanco';
}
