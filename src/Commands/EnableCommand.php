<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class EnableCommand extends Command
{
    protected string $signature = 'hook:enable {name}';

    protected string $description = 'Enable a hook';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');

        $this->hooks->enable($name);

        $this->info("Hook [{$name}] has been enabled.");
    }
}
