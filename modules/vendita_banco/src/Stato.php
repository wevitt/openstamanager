<?php

namespace Modules\VenditaBanco;

use Common\Model;

class Stato extends Model
{
    protected $table = 'vb_stati_vendita';

    public function preventivi()
    {
        return $this->hasMany(Vendita::class, 'idstato');
    }
}
