<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;

// configured for vagrantfile that is included in the project
$conn = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$ch = $conn->channel();

define('EXCHANGE', 'foo-exchange');