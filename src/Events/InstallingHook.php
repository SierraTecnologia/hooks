<?php

namespace Hooks\Events;

class InstallingHook
{
    /**
     * @var string
     */
    public $hook;

    /**
     * Create a new event instance.
     *
     * @param string $hook
     */
    public function __construct($hook)
    {
        $this->hook = $hook;
    }
}
