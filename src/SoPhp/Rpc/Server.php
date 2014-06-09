<?php


namespace SoPhp\Rpc;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use SoPhp\Amqp\ConsumerDescriptor;
use SoPhp\Amqp\EndpointDescriptor;
use SoPhp\Amqp\QueueDescriptor;
use SoPhp\Rpc\Call;
use SoPhp\Rpc\Exception\Server\InvalidArgumentException;
use SoPhp\Rpc\Exception\ServerException;

class Server implements ServiceInterface {
    /** @var  EndpointDescriptor */
    protected $endpoint;
    /** @var  QueueDescriptor */
    protected $queueDescriptor;
    /** @var  mixed|callable */
    protected $delegate;
    /** @var  string|null */
    protected $delegateName;
    /** @var  string */
    protected $tag;
    /** @var  AMQPMessage */
    protected $message;
    /** @var  AMQPChannel */
    protected $channel;

    /**
     * @return callable|mixed
     */
    public function getDelegate()
    {
        return $this->delegate;
    }

    /**
     * @param callable|mixed $delegate
     * @return self
     */
    public function setDelegate($delegate)
    {
        $this->delegate = $delegate;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDelegateName()
    {
        return $this->delegateName;
    }

    /**
     * @param null|string $delegateName
     * @return self
     */
    public function setDelegateName($delegateName)
    {
        $this->delegateName = $delegateName;
        return $this;
    }


    /**
     * @return EndpointDescriptor
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param EndpointDescriptor $endpoint
     * @return self
     */
    public function setEndpoint(EndpointDescriptor $endpoint)
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * @return QueueDescriptor
     */
    public function getQueueDescriptor()
    {
        return $this->queueDescriptor;
    }

    /**
     * @param QueueDescriptor $queueDescriptor
     * @return self
     */
    public function setQueueDescriptor($queueDescriptor)
    {
        $this->queueDescriptor = $queueDescriptor;
        return $this;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     * @return self
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return AMQPMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param AMQPMessage $message
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param AMQPChannel $channel
     * @return self
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel){
        $this->setChannel($channel);
    }



    /**
     * Serve accepts either a class instance (and ignores the second parameter)
     * and routes all method calls to the instance, or a callable (and requires
     * a second parameter) and routes method calls to the callable
     * @param callable|mixed $instanceOrCallable
     * @param null|string $callableName
     */
    public function serve($instanceOrCallable, $callableName = null)
    {
        if(!is_object($instanceOrCallable) && !is_callable($instanceOrCallable)){
            throw new InvalidArgumentException("Must provide either an object or callable to serve");
        } else if(is_callable($instanceOrCallable) && $callableName === null){
            throw new InvalidArgumentException("\$callableName must be provided when serving callables");
        }

        $this->setDelegate($instanceOrCallable);
        $this->setDelegateName($callableName);
    }

    /**
     * Beging handling RPCs
     * @return EndpointDescriptor
     */
    public function start(){
        if(!$this->getDelegate()){
            throw new ServerException("You must specify something to serve");
        }

        $this->initAmqp();
        return $this->getEndpoint();
    }

    protected function initAmqp(){
        if($this->getQueueDescriptor() !== null){
            return;
        }

        $this->setQueueDescriptor(new QueueDescriptor());
        $this->getQueueDescriptor()->declareQueue($this->getChannel());

        $this->setEndpoint(new EndpointDescriptor(null,
            $this->getQueueDescriptor()->getName()));

        /*
        $this->getChannel()->queue_bind($this->getQueueDescriptor()->getName(),
                                        $this->getEndpoint()->getExchange(),
                                        $this->getEndpoint()->getRoute());
        */
        $cd = new ConsumerDescriptor(array($this, 'processMessage'));
        $cd->setTag($this->getTag());
        $cd->setQueueName($this->getQueueDescriptor()->getName());
        $cd->setTicket($this->getQueueDescriptor()->getTicket());
        $cd->consume($this->getChannel());

    }

    /**
     * @param AMQPMessage $msg
     */
    public function processMessage(AMQPMessage $msg)
    {
        $call = Call::fromJson($msg->body);
        try {
            $result = $this->call($call->getMethod(), $call->getArguments());

            $msg->delivery_info['channel']
                ->basic_ack($msg->delivery_info['delivery_tag']);


            // TODO cache message object & reuse
            $response = new AMQPMessage(json_encode($result), array('content_type'=>'text/plain'));
            $response->body = json_encode($result);
            $response->set('correlation_id', $msg->get('correlation_id'));

            $msg->delivery_info['channel']->basic_publish($response, '', $msg->get('reply_to'));

//            $msg->delivery_info['channel']->basic_ack(
//                $msg->delivery_info['delivery_tag']);

        } catch (\Exception $e) {
            // TODO return false
            echo "RPC Server Exception: ".$e->getMessage().PHP_EOL;
        }
    }

    public function wait(){
        $this->getChannel()->wait();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function call($name, $arguments)
    {
        $delegate = $this->getDelegate();
        $callable = is_callable($delegate) ? $delegate : array($delegate, $name);

        if(!is_callable($callable)){
            // TODO return fault
        }
        return call_user_func_array($callable, $arguments);
    }


} 