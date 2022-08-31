<?php declare(strict_types=1);

namespace Trackpoint\DependencyInjector;

use ReflectionException;
use Trackpoint\DependencyInjector\Exception\ContainerException;
use Trackpoint\DependencyInjector\Exception\NotFoundException;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

use Trackpoint\DocComment;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Ds\Map;

class Container implements ContainerInterface{

	private Map $services;
	private Map $bindings;

	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger){

		$this->services = new Map();
		$this->bindings = new Map();

		$this->logger = $logger;

		$this->register($this);
	}

	public function has(
        string $name
    ):bool
    {
		if($name[0] != '\\'){
			$name = '\\'.$name;
		}

		return $this->services->hasKey($name);
	}

	private function getArgumentByParameter(
        ReflectionParameter $parameter,
        $scope = []
    ):object|null
    {
		$name = $parameter->getName();
		if(isset($scope[$name]) || array_key_exists($name, $scope)) {
			return $scope[$name];
		}else if($parameter->hasType()){
			return $this->get((string) $parameter->getType(), $scope);
		}

		return null;
	}
	
	private function getClassRegistrationType(
        ReflectionClass $reflection
    ):Register
    {

        $registers = $reflection->getAttributes(Register::class);
        if(empty($registers)){
            return new Register();
        }

        return $registers[0]->newInstance();
	}

	private function callInstanceConstructor(
        object $instance,
        ReflectionClass $reflection,
        array $scope
    ):void
    {

		$constructor = $reflection->getConstructor();
		if($constructor == null){
			return;
		}

		$args = [];
		$parameters = $constructor->getParameters();
		foreach($parameters as $parameter){
			$args[] = $this->getArgumentByParameter(
                $parameter,
                $scope);
		}

		call_user_func_array([$instance,'__construct'], $args);
	}


    /**
     * @throws ReflectionException
     * @throws ContainerException
     */
    private function build(
        string $name,
        array $scope = []
    ):object
    {

		$reflection = new ReflectionClass($name);

		//$short = $reflection->getShortName();

		//$this->logger->debug('class DocComment metadata', $metadata);

		$register_type = $this->getClassRegistrationType(
            $reflection);

		if($register_type->isDenied()){
			throw ContainerException::prohibitionToCreate();
		}

		/**
		 * Sozdajemo instanc bez konstruktora
		 */
		$instance = $reflection->newInstanceWithoutConstructor();

//Etot funkcional poka ubrana
//		$this->injectInstanceProperties(
//			$instance,
//			$reflection,
//			$scope);

		$this->callInstanceConstructor(
			$instance,
			$reflection,
			$scope);

		
		if($register_type->isSingleton()){
			if($name[0] != '\\'){
				$name = '\\'.$name;
			}

			$this->services->put($name, $instance);
		}

		return $instance;
	}

	public function register(
        object $instance
    ):void
    {
		$name = '\\'.get_class($instance);

		$this->logger->debug('register class ', [
			'name' => $name
		]);

		if($this->has($name)){
			return;
		}

		$this->services->put($name, $instance);
	}

	public function get(
        string $name,
        $scope = []
    ):object
    {

		if($name[0] != '\\'){
			$name = '\\'.$name;
		}

		$this->logger->debug('get class ', [
			'name' => $name
		]);

		if($this->has($name)){
			return $this->services->get($name);
		}

		$concrete = $this->bindings->get($name, null);
		if($concrete != null){
			$name = $concrete;

			if($this->has($name)){
				return $this->services->get($name);
			}

		}

		if(class_exists($name)==false){
			throw new NotFoundException(sprintf('No entry or class found for "%s"',
				$name));
		}

		$instance = $this->build($name, $scope);

		return $instance;
	}

    /**
     * @throws ReflectionException
     */
    public function call(
        object $instance,
        string $method,
        $scope = []
    ):mixed {
		$name = '\\'.get_class($instance);
		
		$this->logger->debug('call instance method', [
			'class' => $name,
			'method' => $method,
		]);

		$reflection = new ReflectionMethod($name, $method);
		$parameters = $reflection->getParameters();

		$args = [];
		foreach($parameters as $parameter){
			$args[] = $this->getArgumentByParameter($parameter, $scope);
		}

		return call_user_func_array([$instance, $method], $args);
	}

	public function bind(
        string $abstract,
        string $concrete
    ):void
    {
		if($abstract[0] != '\\'){
			$abstract = '\\'.$abstract;
		}

		if($concrete[0] != '\\'){
			$concrete = '\\'.$concrete;
		}

		$this->bindings->put($abstract, $concrete);
	}


}