<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class UninstallCommand extends Command
{
    protected string $signature = 'hook:uninstall {name} {--delete} {--no-unmigrate} {--no-unseed} {--no-unpublish}';

    protected string $description = 'Uninstall a hook';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');

        $this->hooks->uninstall(
            $name,
            $this->option('delete'),
            !$this->option('no-unmigrate'),
            !$this->option('no-unseed'),
            !$this->option('no-unpublish')
        );

        $this->info("Hook [{$name}] has been uninstalled.");
    }
}
