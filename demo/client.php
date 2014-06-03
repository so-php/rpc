<?php

use SoPhp\Amqp\EndpointDescriptor;
use SoPhp\Rpc\Client;

require_once 'bootstrap.php';

if(count($argv) < 3){
    die("Usage:\n\tphp client.php <exchange> <route> <name>\n\n");
} else {
    $exchange = $argv[1];
    $route = $argv[2];
    $name = @$argv[3] ?: uniqid('john_');
}

$endpoint = new EndpointDescriptor($exchange, $route);

$client = new Client($endpoint, $ch);

echo $client->call('greet', $name) . PHP_EOL;