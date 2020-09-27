<?php

namespace Hooks\Commands;

use Hooks\Composer;
use Hooks\Events\Setup;
use Hooks\HooksServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    const REPOSITORY_NAME = 'hooks';

    protected $signature = 'hook:setup {--url=https://sierratecnologia.io}';

    protected $description = 'Prepare Composer for using Hooks.';

    protected Filesystem $filesystem;

    public function handle(): void
    {
        $composer = new Composer(base_path('composer.json'));

        $composer->addRepository(
            static::REPOSITORY_NAME,
            [
            'type' => 'composer',
            'url'  => $this->option('url'),
            ]
        );

        if (Str::startsWith($this->option('url'), 'http://')) {
            $composer->addConfig('secure-http', false);
        }

        $composer->save();

        $this->call('vendor:publish', ['--provider' => HooksServiceProvider::class]);

        $this->info('Hooks are now ready to use! Go ahead and try to "php artisan hook:install test-hook"');

        event(new Setup());
    }
}
