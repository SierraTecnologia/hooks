<?php

namespace Hooks\Events;

class MakingHook
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
