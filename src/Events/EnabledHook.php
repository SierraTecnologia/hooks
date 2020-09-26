<?php

namespace Hooks\Events;

use Hooks\Hook;

class EnabledHook
{
    public Hook $hook;

    /**
     * Create a new event instance.
     *
     * @param \Hooks\Hook $hook
     */
    public function __construct(Hook $hook)
    {
        $this->hook = $hook;
    }
}
