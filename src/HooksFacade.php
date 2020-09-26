<?php

namespace Hooks;

use Illuminate\Support\Facades\Facade;

class HooksFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return Hooks::class
     */
    protected static function getFacadeAccessor()
    {
        return Hooks::class;
    }
}
