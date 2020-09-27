<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class ListCommand extends Command
{
    protected $signature = 'hook:list';

    protected $description = 'List installed hooks';

    protected Hooks $hooks;

    public function handle(): void
    {
        $this->table(
            ['Name', 'Status'], $this->hooks->hooks()->transform(
                function ($hook) {
                    return [
                    'name'    => $hook['name'],
                    'enabled' => $hook['enabled'] ? 'Enabled' : 'Disabled',
                    ];
                }
            )
        );
    }
}
