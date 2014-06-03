<?php


namespace SoPhp\Rpc\Proxy;


use SoPhp\Rpc\Proxy\Exception\BuildFailed;
use Zend\Code\Generator\ClassGenerator;

class ProxyBuilder {
    /** @var array */
    protected $implements = array();

    /**
     * @return array
     */
    public function getImplements()
    {
        return $this->implements;
    }

    /**
     * Specify the interface(s) proxy should implement
     * @param string|string[] $fullyQualifiedClassName
     */
    public function setImplements($fullyQualifiedClassName){
        $this->implements = is_array($fullyQualifiedClassName)
            ? $fullyQualifiedClassName : array($fullyQualifiedClassName);
    }


    /**
     * Generates a dynamic class implementing specified interfaces (and ClientAwareInterface).
     * @return string class name
     */
    public function build(){
        $generator = new ClassGenerator(uniqid('Proxy_'), 'SoPhp\Rpc\Proxy');
        $generator->setImplementedInterfaces($this->getImplements());
        $generator->setExtendedClass('\SoPhp\Rpc\Proxy\ProxyAbstract');
        $source = $generator->generate();
        $className = $generator->getNamespaceName() . '\\' . $generator->getName();

        try {
            eval($source);
        } catch (\Exception $e) {
            throw new BuildFailed("Could not evaluate source code for proxy. ".$source, 0, $e);
        }

        if(!class_exists($className,false)) {
            throw new BuildFailed("Proxy class `$className` does not exist");
        }

        return $className;
    }
} 