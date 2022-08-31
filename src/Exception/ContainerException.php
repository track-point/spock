<?php declare(strict_types=1);

namespace Trackpoint\DependencyInjector\Exception;

use Psr\Container\ContainerExceptionInterface;

use Exception;

class ContainerException extends Exception implements ContainerExceptionInterface{


    public static function prohibitionToCreate(): ContainerException
    {
        return new self('Class is not allowed to be created through the dependency injector');
    }
}

