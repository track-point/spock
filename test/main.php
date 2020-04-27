<?php declare(strict_types=1);

namespace Test;

use Trackpoint\DependencyInjector\Container;
use Psr\Log\LoggerInterface;

include dirname(__DIR__).'/vendor/autoload.php';


$logger = new class implements LoggerInterface
{

	public function emergency($message, array $context = array())
	{
		$this->log('emergency', $message, $context);
	}

	public function alert($message, array $context = array())
	{
		$this->log('alert', $message, $context);
	}

	public function critical($message, array $context = array())
	{
		$this->log('critical', $message, $context);
	}

	public function error($message, array $context = array())
	{
		$this->log('error', $message, $context);
	}

	public function warning($message, array $context = array())
	{
		$this->log('warning', $message, $context);
	}

	public function notice($message, array $context = array())
	{
		$this->log('notice', $message, $context);
	}

	public function info($message, array $context = array())
	{
		$this->log('info', $message, $context);
	}

	public function debug($message, array $context = array())
	{
		$this->log('debug', $message, $context);
	}

	public function log($level, $message, array $context = array())
	{

		error_log(sprintf(
			'[%s] %s',
			$level,
			sprintf($message, ...$context)
		));
	}
};


class Test1{

	/**
	 * @DI::inject()
	 */
	public Test2 $test;

	public function __construct() {
		error_log("Test1 - construct");
	}
}

/**
 * @DI::register(type=Singleton)
 */
class Test2{

	/**
	 * @DI::inject(setter=setTest)
	 */
	public Test1 $test;

	public function __construct() {
		error_log("Test2 - construct");
	}

	public function setTest(Test1 $test){

	}

	public function fobar(Test1 $test, Test2 $test2){
		error_log('fobar');
		var_dump($test);
		//var_dump($test2);
		
	}
}


$container = new Container($logger);

$test2 = $container->get('\Test\Test2');
$test1 = $container->get('\Test\Test1');

$container->call($test2,'fobar');

//var_dump($test1->test);
//var_dump($test2->test);

