<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class DisableCommand extends Command
{
    protected string $signature = 'hook:disable {name}';

    protected string $description = 'Disable a hook';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');

        $this->hooks->disable($name);

        $this->info("Hook [{$name}] has been disabled.");
    }
}
