<?php


namespace SoPhp\Rpc;


use SoPhp\Rpc\Client;

interface ClientAwareInterface {
    /**
     * @param Client $client
     */
    public function __setClient(Client $client);

    /**
     * @return Client
     */
    public function __getClient();
} 