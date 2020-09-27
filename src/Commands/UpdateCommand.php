<?php

namespace Hooks\Commands;

use Illuminate\Console\Command;
use Hooks\Hooks;

class UpdateCommand extends Command
{
    protected $signature = 'hook:update {name} {version?} {--no-migrate} {--no-seed} {--no-publish} {--force}';

    protected $description = 'Update a hook';

    protected Hooks $hooks;

    public function handle()
    {
        $name = $this->argument('name');

        $hooks = $this->hooks->hooks();

        $version = $this->argument('version');

        $hooks->where('name', $name)->first();

        $updated = $this->hooks->update(
            $name,
            $version,
            !$this->option('no-migrate'),
            !$this->option('no-seed'),
            !$this->option('no-publish'),
            $this->option('force')
        );

        return $updated
            ? $this->info("Hook [{$name}] has been updated!")
            : $this->info('Nothing to update.');
    }
}
