<?php declare(strict_types=1);

namespace Test;

use Trackpoint\DependencyInjector\Container;
use Psr\Log\LoggerInterface;
use Trackpoint\DependencyInjector\Register;
use Trackpoint\DependencyInjector\Inject;

include dirname(__DIR__).'/vendor/autoload.php';


class Logger implements LoggerInterface
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

/*
		error_log(sprintf(
			'[%s] %s',
			$level,
			sprintf($message, ...$context)
		));
*/
	}
};


$logger = new Logger();

#[Register(Register::SINGLETON)]
class Test1{
	private Logger $logger;

	public function __construct(
        Logger $logger
    ) {
		error_log("Test1 - construct");
        $this->logger = $logger;
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

	public function __construct(
        Test1 $test
    ) {
		error_log("Test2 - construct");
        $this->test=$test;
	}


	public function fobar(
        Test1 $test,
        Test2 $test2
    ){
		error_log('fobar');
		var_dump($test);
		//var_dump($test2);
		
	}
}


$container = new Container($logger);
$container->register($logger);

$test1 = $container->get(Test2::class);

//$test2 = $container->get('\Test\Test2',[
//	'abc' => 123
//]);
//$test1 = $container->get('\Test\Test1');

//$container->call($test2,'fobar');

//var_dump($test1->test);
//var_dump($test2->test);

//$container->bind(LoggerInterface::class, Logger::class);
//$loger2 = $container->get(LoggerInterface::class);
//$loger2->debug('test');

//var_dump($loger2 === $logger);