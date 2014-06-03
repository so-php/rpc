# rpc

RPC Pattern via php-amqplib

## Avoiding Technology Lockout
One of the goals of ths implementation is to prevent locking out other technologies like python, ruby and java from producing or consuming requests. This is not a terribly difficult task--it just means we need to use a non-proprietary message queue (php-amqplib + rabbitmq) and a message format that isn't language specific. Hence we choose to use a json serialized data structure rather than a PHP serialized string.

That being said, there are no enforced restrictions on the content of the PRC params. It is up to developers to keep vigilant and be sure not to stuff anything in an RPC that is PHP or platform specific.

## Message Structure
As stated above, the message is a plain Json string. The structure is an object with three keys (only) at the top level.

  * `method` the method name
  * `params` an array to hold rpc params. No imposed limits other than technical feasibility and reasonableness. Params should be present even if empty `[]`. Params are ordered, and will be provided to the RP in the same order they are received.

```
{
    "name": "sayHelloTo",
    "params": [
        "bob"
    ]
}
```

## Usage
Using RPC is pretty straight forward.
Words in uppercase are values that need to be supplied/configured.

### Server
First we need to start an RPC Server queue and bind it a class/method.

```
$conn = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$ch = $conn->channel();

$server = new Server($ch);
$server->serve(function($name){ return "Hello $name"; }, 'greet');
// or more commonly:
// $rpc->serve(new Greeter()); // where greeter has a greet method.

echo "listening for RPCs @ " . $server->getQueueName();
while(true){
    $server->wait();
}
```
Output might look something like:

    `Listening for RPCs @ amq.gen-v1ac3`

### Client
The client looks a bit like this:
```
// need a channel to work with
$conn = new AMQPConnection(HOST, PORT, USER, PASS, VHOST);
$ch = $conn->channel();

// we have to tell the client what queue to use
$rpc = new Client($ch, 'amq.gen-v1ac3');

$greeting = $rpc->greet('bob');
echo $greeting;
```

Output might look like:

    `Hello bob`

#### Wait, wait, wait...
"You mean I have to somehow orchestrate configuring my RPC client with the correct queue for the server?" I hear you asking. The answer is, unapologetically, yes. But _you_ shouldn't do that, you should use a service registry pattern for that. Try [so-php/service-registry](https://github.com/so-php/service-registry).


