<?php

namespace Modules\VenditaBanco;

interface ConnettoreInterface
{
    /**
     * Invia il documento al registratore secondo il protocollo previsto.
     *
     * @throws \Exception
     */
    public function printDocument(Vendita $documento, $codice_lotteria, $show_price);
}
