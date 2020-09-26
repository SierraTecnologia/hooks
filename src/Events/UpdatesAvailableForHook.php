<?php

namespace Hooks\Events;

class UpdatesAvailableForHook
{
    /**
     * @var string
     */
    public $hook;

    /**
     * Create a new event instance.
     *
     * @param string $hook
     * @param string $version
     */
    public function __construct($hook, $version)
    {
        $this->hook = $hook;
    }
}
