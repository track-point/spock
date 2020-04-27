<?php declare(strict_types=1);

namespace Test;

use Trackpoint\DependencyInjector\Container;

include dirname(__DIR__).'/vendor/autoload.php';

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


$container = new Container();

$test2 = $container->get('\Test\Test2');
$test1 = $container->get('\Test\Test1');

$container->call($test2,'fobar');

//var_dump($test1->test);
//var_dump($test2->test);

