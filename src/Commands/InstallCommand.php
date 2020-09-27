<?php

namespace Hooks\Commands;

use Hooks\Hooks;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'hook:install {name} {version?} {--enable} {--no-migrate} {--no-seed} {--no-publish}';

    protected $description = 'Download and install a hook from remote https://sierratecnologia.io';

    protected Hooks $hooks;

    public function handle(): void
    {
        $name = $this->argument('name');

        $this->hooks->install(
            $name,
            $this->argument('version'),
            !$this->option('no-migrate'),
            !$this->option('no-seed'),
            !$this->option('no-publish')
        );

        if ($this->option('enable')) {
            $this->hooks->enable($name);

            $this->info("Hook [{$name}] has been installed and enabled.");
        } else {
            $this->info("Hook [{$name}] has been installed.");
        }
    }
}
