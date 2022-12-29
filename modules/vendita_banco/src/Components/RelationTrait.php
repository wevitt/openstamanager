<?php

namespace Modules\VenditaBanco\Components;

use Modules\VenditaBanco\Vendita;

trait RelationTrait
{
    protected $disableOrder = true;

    public function getDocumentID()
    {
        return 'idvendita';
    }

    public function document()
    {
        return $this->belongsTo(Vendita::class, $this->getDocumentID());
    }

    public function getParentID()
    {
        return 'idvendita';
    }

    public function parent()
    {
        return $this->belongsTo(Vendita::class, $this->getParentID());
    }

    public function vendita()
    {
        return $this->parent();
    }

    public function fixIvaIndetraibile()
    {
    }

    public function incorporaIva()
    {
        return true;
    }
}
