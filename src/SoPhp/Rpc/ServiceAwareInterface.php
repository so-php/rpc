<?php


namespace SoPhp\Rpc;


interface ServiceAwareInterface {
    /**
     * @param ServiceInterface $service
     */
    public function __setService(ServiceInterface $service);

    /**
     * @return ServiceInterface
     */
    public function __getService();
} 