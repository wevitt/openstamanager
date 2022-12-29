<?php

namespace Modules\VenditaBanco\Components;

use Common\Components\Row;

class Riga extends Row
{
    use RelationTrait;

    protected $table = 'vb_righe_venditabanco';
}
