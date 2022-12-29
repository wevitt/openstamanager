<?php

namespace Modules\VenditaBanco;

class Stampante
{
    /**
     * @var string
     */
    protected $ip;
    /**
     * @var string
     */
    protected $port;
    /**
     * @var bool
     */
    protected $is_fiscale;

    /**
     * Stampante constructor.
     *
     * @param string $ip
     * @param string $port
     * @param bool   $is_fiscale
     */
    public function __construct($ip, $port, $is_fiscale = true)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->setFiscale($is_fiscale);
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return bool
     */
    public function isFiscale()
    {
        return $this->is_fiscale;
    }

    /**
     * @param bool $fiscale
     */
    public function setFiscale($fiscale)
    {
        $this->is_fiscale = $fiscale;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->getIP());
    }
}
