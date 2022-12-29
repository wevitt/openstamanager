<?php

namespace Modules\VenditaBanco\Components;

use Common\Components\Discount;

class Sconto extends Discount
{
    use RelationTrait;

    protected $table = 'vb_righe_venditabanco';

    /**
     * Imposta il prezzo unitario secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     *
     * @param $value
     * @param $type
     */
    public function setScontoUnitario($prezzo_unitario, $id_iva)
    {
        $this->id_iva = $id_iva;

        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            $this->sconto_unitario_ivato = $prezzo_unitario;
        } else {
            $this->sconto_unitario = $prezzo_unitario;
        }
    }
}
