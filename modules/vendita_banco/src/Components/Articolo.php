<?php

namespace Modules\VenditaBanco\Components;

use Common\Components\Article;
use Modules\Articoli\Movimento;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'vb_righe_venditabanco';

    protected function movimentaMagazzino($qta)
    {
        $documento = $this->parent;
        $data = $documento->getReferenceDate();

        $date = $documento->data;
        $date = explode(' ', $date);
        $date = $date[0];
        $date = explode('-', $date);
        $date = $date[2].'/'.$date[1].'/'.$date[0];

        $qta_movimento = $documento->direzione == 'uscita' ? $qta : -$qta;
        $movimento = Movimento::descrizioneMovimento($qta_movimento, $documento->direzione).' - Vendita al banco num. '.$documento->numero_esterno.' del '.$date;

        $partenza = $documento->idmagazzino;
        $arrivo = 0;

        $this->articolo->movimenta($qta_movimento, $movimento, $data, false, [
            'reference_type' => get_class($documento),
            'reference_id' => $documento->id,
            'idsede' => $partenza,
        ]);
    }
}
