<?php declare(strict_types=1);

namespace Trackpoint\DependencyInjector\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface{

}