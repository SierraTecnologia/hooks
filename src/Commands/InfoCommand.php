<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class InfoCommand extends Command
{
    protected string $signature = 'hook:info {name}';

    protected string $description = 'Get information on a hook';

    protected Hooks $hooks;

    public function handle()
    {
        $name = $this->argument('name');

        $hook = $this->hooks->hooks()->where('name', $name)->first();

        if (is_null($hook)) {
            return $this->error("Hook [{$name}] not found.");
        }

        $this->comment($name);
        $this->line("  <info>Name:</info>     {$name}");
        $this->line('  <info>Status:</info>   '.($hook['enabled'] ? 'Enabled' : 'Disabled'));
        $this->line('  <info>Version:</info>  '.(!is_null($hook['version']) ? $hook['version'] : 'None'));
    }
}
