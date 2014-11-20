<?php

namespace Sokil\FraudDetector;

class ProcessorList implements \Iterator, \Countable
{
    private $processorNamespaces = array(
        '\Sokil\FraudDetector\Processor',
    );
    
    /**
     * Processor instances
     * @var array
     */
    private $processorList = array();
    
    /**
     * List of processor declarations used to initiate processor
     * @var array
     */
    private $processorDeclarationList = array();
    
    private $lastSequence = 0;
    
    public function registerNamespace($namespace)
    {
        $this->processorNamespaces[] = $namespace;
        return $this;
    }
    
    public function declareProcessor($name, $callable, $priority = 0)
    {
        $this->processorDeclarationList[$name] = array(
            'callable'  => $callable, 
            'priority'  => $priority,
            'sequence'  => $this->lastSequence++,
        );
        
        return $this;
    }
    
    /**
     * Factory method to create new check condition
     * 
     * @param string $name name of check condition
     * @return \Sokil\FraudDetector\AbstractProcessor
     * @throws \Exception
     */
    private function createProcessor($name)
    {
        // check if processor declared
        if(!$this->isProcessorDeclared($name)) {
            throw new \Exception('Processor ' . $name . ' not declared');
        }
        
        $className = ucfirst($name) . 'Processor';
        
        foreach($this->processorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(!class_exists($fullyQualifiedClassName)) {
                continue;
            }
            
            $processor =  new $fullyQualifiedClassName($this);

            // get declaration of processor
            $configuratorCallable = $this->processorDeclarationList[$name]['callable'];

            // configure condition
            if($configuratorCallable && is_callable($configuratorCallable)) {
                call_user_func($configuratorCallable, $processor);
            }

            return $processor;
        }
        
        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }
    
    /**
     * 
     * @param type $name
     * @return \Sokil\FraudDetector\AbstractProcessor
     * @throws \Exception
     */
    public function getProcessor($name)
    {
        // return if already initialised
        if(!isset($this->processorList[$name])) {
            $this->processorList[$name] = $this->createProcessor($name);
        }
        
        return $this->processorList[$name];
    }
    
    public function isProcessorDeclared($name)
    {
        return isset($this->processorDeclarationList[$name]);
    }
    
    public function count()
    {
        return count($this->processorDeclarationList);
    }
    
    public function rewind()
    {
        uasort($this->processorDeclarationList, function($declaration1, $declaration2) {
            if($declaration1['priority'] === $declaration2['priority']) {
                return $declaration1['sequence'] > $declaration2['sequence'] ? 1 : -1;
            }
            
            return $declaration1['priority'] > $declaration2['priority'] ? 1 : -1;
        });
        
        reset($this->processorDeclarationList);
    }
    
    public function current()
    {
        return $this->getProcessor($this->key());
    }
    
    public function key()
    {
        return key($this->processorDeclarationList);
    }
    
    public function next()
    {
        next($this->processorDeclarationList);
    }
    
    public function valid()
    {
        return null !== $this->getKey();
    }
}