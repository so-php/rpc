<?php
use SoPhp\Rpc\Server;

require_once 'bootstrap.php';

$server = new Server($ch);
$server->serve(function($name){
    return "Hello $name, how are you?";
}, 'greet');
$endpoint = $server->start();
echo "RPC Server started, point clients to " . $endpoint . PHP_EOL;
while(count($ch->callbacks)){
    $ch->wait();
}
