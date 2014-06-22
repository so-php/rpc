<?php


namespace SoPhp\Rpc;


use SoPhp\Amqp\EndpointDescriptor;

interface ServiceInterface {
    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function call($name, $arguments);

    /**
     * @return EndpointDescriptor
     */
    public function getEndpoint();

    /**
     * @param EndpointDescriptor $endpoint
     */
    public function setEndpoint(EndpointDescriptor $endpoint);
} 