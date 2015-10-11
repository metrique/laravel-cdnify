<?php

namespace Metrique\CDNify;

use Illuminate\Support\Facades\Facade;

class CDNifyFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return '\Metrique\CDNify\CDNifyRepository';
    }
}