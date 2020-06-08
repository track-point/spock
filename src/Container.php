<?php declare(strict_types=1);

namespace Trackpoint\DependencyInjector;

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

	private const DC_NAMESPACE = 'DI';

	private const DC_REGISTER  = 'REGISTER';
	private const DC_REGISTER_TYPE = 'TYPE';
	private const DC_REGISTER_TYPE_SINGLETON = 'SINGLETON';
	private const DC_REGISTER_TYPE_DENIED    = 'DENIED';
	
	private const DC_INJECT          = 'INJECT';
	private const DC_INJECT_REQUIRED = 'REQUIRED';
	private const DC_INJECT_SETTER   = 'SETTER';

	private Map $services;
	private Map $bindings;

	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger){

		$this->services = new Map();
		$this->bindings = new Map();

		$this->logger = $logger;

		$this->register($this);
	}

	public function has($name) : bool{
		if($name[0] != '\\'){
			$name = '\\'.$name;
		}

		return $this->services->hasKey($name);
	}

	private function getArgumentByParametr(ReflectionParameter $parameter, $scope = []){
		$name = $parameter->getName();
		if(isset($scope[$name]) || array_key_exists($name, $scope)) {
			return $scope[$name];
		}else if($parameter->hasType()){

			return $this->get((string) $parameter->getType(), $scope);
		}

		return null;
	}
	
	private function getClassRegistrationType($metadata){
		$type = $metadata[self::DC_NAMESPACE][self::DC_REGISTER][self::DC_REGISTER_TYPE] ?? null;
		if($type == null){
			return null;
		}

		return strtoupper($type);
	}


	private function getPropertyOptions($metadata){
		return $metadata[self::DC_NAMESPACE][self::DC_INJECT] ?? null;
	}


	private function injectInstanceProperties($instance, $reflection, $scope){

		$properties = $reflection->getProperties();
		foreach($properties as $propertie){

			/**
			 * Esli net metadannih dlja DI propuskaem
			 */
			$comment = $propertie->getDocComment();
			if($comment == false){
				continue;
			}

			$options = $this->getPropertyOptions(DocComment::parse($comment));
			if($options == null){
				continue;
			}

			$required = (bool) ($options[self::DC_INJECT_REQUIRED] ?? false);

			if(array_key_exists($propertie->getName(), $scope)) {
				$dependence = $scope[$propertie->getName()];
			}else if($propertie->hasType()){
				$dependence = $this->get((string) $propertie->getType(), $scope);
			}else{

				if($required){
					throw new ContainerException(sprintf('No value for required property %s',
						$propertie->getName()));
				}

				continue;
			}


			/**
			 * Esli u svojstva est setter to zavisemost naznachaetsja cherez nego
			 */
			if(($setter = $options[self::DC_INJECT_SETTER] ?? null)){
				if($reflection->hasMethod($setter) == false){
					throw new ContainerException(sprintf('Unknown setter %s for %s',
						$setter, 
						$name));
				}

				(function ($name, $value) {
					$this->$name($value);
				})->call($instance, $setter, $dependence);

			}else{
				(function ($name, $value) {
					$this->$name = $value;
				})->call($instance, $propertie->getName(), $dependence);
			}
		}
	}


	private function callInstanceConstructor($instance, $reflection, $scope){
		$constructor = $reflection->getConstructor();
		if($constructor == null){
			return;
		}

		$args = [];
		$parameters = $constructor->getParameters();
		foreach($parameters as $parameter){
			$args[] = $this->getArgumentByParametr($parameter, $scope);
		}

		call_user_func_array([$instance,'__construct'], $args);
	}



	private function build($name, $scope = []){

		$reflection = new ReflectionClass($name);
		$short = $reflection->getShortName();
		$metadata = DocComment::parse($reflection->getDocComment());

		$this->logger->debug('class DocComment metadata', $metadata);

		$register_type = $this->getClassRegistrationType($metadata);

		if($register_type == self::DC_REGISTER_TYPE_DENIED){
			throw new ContainerException('Class is not allowed to be created through the dependency injector');
		}

		/**
		 * Sozdajemo instanc bez konstruktora
		 */
		$instance = $reflection->newInstanceWithoutConstructor();

		$this->injectInstanceProperties(
			$instance,
			$reflection,
			$scope);

		$this->callInstanceConstructor(
			$instance,
			$reflection,
			$scope);

		
		if($register_type == self::DC_REGISTER_TYPE_SINGLETON){
			if($name[0] != '\\'){
				$name = '\\'.$name;
			}

			$this->services->put($name, $instance);
		}

		return $instance;
	}

	public function register($instance):void{
		$name = '\\'.get_class($instance);

		$this->logger->debug('register class ', [
			'name' => $name
		]);

		if($this->has($name)){
			return;
		}

		$this->services->put($name, $instance);
	}

	public function get($name, $scope = []){

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

	public function call($instance, $method, $scope = []){
		$name = '\\'.get_class($instance);
		
		$this->logger->debug('call instance method', [
			'class' => $name,
			'method' => $method,
		]);

		$reflection = new ReflectionMethod($name, $method);
		$parameters = $reflection->getParameters();

		$args = [];
		foreach($parameters as $parameter){
			$args[] = $this->getArgumentByParametr($parameter, $scope);
		}

		return call_user_func_array([$instance, $method], $args);
	}

	public function bind($abstract, $concrete){
		if($abstract[0] != '\\'){
			$abstract = '\\'.$abstract;
		}

		if($concrete[0] != '\\'){
			$concrete = '\\'.$concrete;
		}

		$this->bindings->put($abstract, $concrete);
	}


}