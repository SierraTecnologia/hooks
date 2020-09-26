<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Hooks\Hooks;

class MakeCommand extends Command
{
    protected string $signature = 'hook:make {name}';

    protected string $description = 'Make a hook';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');
        $name = Str::kebab($name);

        $this->hooks->make($name);

        $this->info("Hook [{$name}] has been made.");
    }
}
