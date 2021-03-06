<?php


namespace SoPhp\Rpc;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use SoPhp\Amqp\EndpointDescriptor;
use SoPhp\Amqp\QueueDescriptor;
use SoPhp\Rpc\Exception\RpcException;
use SoPhp\Rpc\Exception\Server\TimeoutException;

/**
 * Class Client
 * @package SoPhp\Framework\Rpc
 * TODO use behaviour pattern to supply default/exception-handling behaviour
 */
class Client implements ServiceInterface {
    /** @var  AMQPChannel */
    protected $channel;
    /** @var  EndpointDescriptor */
    protected $endpoint;
    /** @var  AMQPMessage */
    protected $message;
    /** @var  QueueDescriptor */
    protected $queueDescriptor;
    /** @var  string */
    protected $requestId;
    /** @var  string */
    protected $response;
    /** @var  int milliseconds */
    protected $timeout = 250;

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
     * @return QueueDescriptor
     */
    public function getQueueDescriptor()
    {
        return $this->queueDescriptor;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     * @return self
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param string $requestId
     * @return self
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return self
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }




    /**
     * @param EndpointDescriptor $endpoint
     */
    public function __construct(EndpointDescriptor $endpoint, AMQPChannel $channel){
        $this->setChannel($channel);
        $this->setEndpoint($endpoint);
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function call($method, $params){
        $this->initAmqp();

        $body = json_encode((object)array(
            'method' => $method,
            'params' => $params
        ));

        $this->setRequestId(uniqid());

        $message = $this->getMessage();
        if(!$message){
            $message = new AMQPMessage($body, array(
                'content_type' => 'text/plain',
                'reply_to' => $this->getQueueDescriptor()->getName(),
                'correlation_id' => $this->getRequestId()
            ));
        } else {
            $message->body = $body;
            $message->set('correlation_id', $this->getRequestId());
            $this->setMessage($message);
        }

        $endpoint = $this->getEndpoint();
        $this->getChannel()->basic_publish($message,
            $endpoint->getExchange(),
            $endpoint->getRoute());

        $this->getChannel()->basic_consume(
            $this->getQueueDescriptor()->getName(),
            $this->getQueueDescriptor()->getName(),
            false,
            true, // todo make false (& handle no-ack?)
            false,
            false,
            array($this, 'processMessage')
        );

        $this->setResponse(null);
        $start = microtime(true);
        while ($this->getResponse() === null) {
            if(microtime(true) - $start > $this->getTimeout()/1000){
                throw new TimeoutException("RPC call timed out");
            }
            try {
                $this->getChannel()->wait(null, null, 0.001);
            } catch (AMQPTimeoutException $e) {
                // no-opp
            }
        }

        return $this->getResponse();
    }

    /**
     * @param AMQPMessage $message
     */
    public function processMessage(AMQPMessage $message){
        $id = $message->get('correlation_id');
        if($id === $this->getRequestId()){
            if($message->body === null){
                $this->setResponse('TODO'); // TODO fault handling
            } else {
                $this->setResponse( $message->body );
            }
        }

    }

    protected function initAmqp(){
        if($this->queueDescriptor){
            return;
        }

        $this->queueDescriptor = new QueueDescriptor(null, false, false, true, true);
        $this->queueDescriptor->declareQueue($this->getChannel());
    }

} 