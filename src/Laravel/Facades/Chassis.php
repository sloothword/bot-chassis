<?php

namespace Chassis\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Telegram.
 */
class Chassis extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'chassis';
    }
}
