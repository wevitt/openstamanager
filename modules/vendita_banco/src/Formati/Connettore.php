<?php

namespace Modules\VenditaBanco\Formati;

use Exception;
use Modules\VenditaBanco\ConnettoreInterface;
use Modules\VenditaBanco\Stampante;

abstract class Connettore implements ConnettoreInterface
{
    /**
     * @var Stampante
     */
    protected $stampante;

    public function __construct(Stampante $stampante)
    {
        $this->stampante = $stampante;
    }

    /**
     * @param array $message
     *
     * @throws Exception
     */
    protected function sendMessage($message = [])
    {
        // Apertura della connessione TCP
        $fp = stream_socket_client('tcp://'.$this->stampante->getIp().':'.$this->stampante->getPort(), $errno, $errstr, 3);
        if (!$fp) {
            throw new Exception($errstr.' ('.$errno.')');
        }

        // Invio del messaggio e chiusura della connessione
        $message_format = implode("\n", $message);
        fwrite($fp, $message_format, strlen($message_format));
        fclose($fp);
    }
}
