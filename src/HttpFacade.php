<?php

namespace Febalist\LaravelHttp;

use Illuminate\Support\Facades\Facade;

class HttpFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return HttpServiceProvider::$abstract;
    }
}
