<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class CheckCommand extends Command
{
    protected $signature = 'hook:check';

    protected $description = 'Check for updates and show hooks that can be updated';

    protected Hooks $hooks;

    public function handle(): void
    {
        $hooks = $this->hooks->checkForUpdates();

        $count = $hooks->count();

        $this->info(($count == 1 ? '1 update' : $count.' updates').' available.');

        foreach ($hooks as $hook) {
            $this->comment($hook->name.' '.$hook->version);
        }
    }
}
