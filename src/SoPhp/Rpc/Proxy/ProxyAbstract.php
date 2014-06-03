<?php


namespace SoPhp\Rpc\Proxy;


use SoPhp\Rpc\Client;
use SoPhp\Rpc\ClientAwareInterface;

abstract class ProxyAbstract implements ClientAwareInterface {
    /** @var  Client */
    private $client;
    /**
     * @param Client $client
     */
    public function __setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function __getClient()
    {
        return $this->client;
    }

    /**
     * @param string $name
     * @param array $params
     */
    public function __call($name, $params){
        return $this->__getClient()->call($name, $params);
    }
}