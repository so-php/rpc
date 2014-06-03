<?php


namespace SoPhp\Rpc;


class Call {
    /** @var  string */
    protected $method;
    /** @var  array */
    protected $arguments;

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     * @return self
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return self
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param $method
     * @param array $arguments
     */
    public function __construct($method, array $arguments = array()){
        $this->setMethod($method);
        $this->setArguments($arguments);
    }


    /**
     * @return string
     */
    public function toJson(){
        return json_encode((object)array(
            'method' => $this->getMethod(),
            'params' => $this->getArguments()
        ));
    }

    /**
     * @param $json
     * @return Call
     */
    public static function fromJson($json){
        $obj = json_decode($json);
        return new Call($obj->method, (array)$obj->params);
    }
} 