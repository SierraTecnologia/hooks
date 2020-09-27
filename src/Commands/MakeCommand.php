<?php

namespace Hooks\Commands;

use Hooks\Hooks;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    protected $signature = 'hook:make {name}';

    protected $description = 'Make a hook';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');
        $name = Str::kebab($name);

        $this->hooks->make($name);

        $this->info("Hook [{$name}] has been made.");
    }
}
