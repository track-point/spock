<?php

namespace Trackpoint\DependencyInjector;

use Attribute;

#[Attribute]
class Register
{
    const SINGLETON = 1;
    const DENIED = 2;

    private int $type;
    //private const DC_REGISTER_TYPE_SINGLETON = 'SINGLETON';
    //private const DC_REGISTER_TYPE_DENIED    = 'DENIED';

    public function __construct(int $type=0){
        $this->type=$type;
    }


    public function isDenied():bool
    {
        return $this->type == self::DENIED;
    }

    public function isSingleton():bool
    {
        return $this->type == self::SINGLETON;
    }


}