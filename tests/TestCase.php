<?php

namespace Hooks\Tests;

use Illuminate\Filesystem\Filesystem;
use Hooks\Composer;
use Hooks\Hooks;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Hooks\HooksServiceProvider'];
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = app(Filesystem::class);

        // Cleanup old hooks before testing
        $filesystem->deleteDirectory(base_path('hooks'));

        // Cleanup published files
        $filesystem->deleteDirectory(base_path('public/vendor'));

        // Clear old hooks
        $hook = app(Hooks::class);
        $hook->readJsonFile();

        // Delete testbench's fixures tests folder
        $filesystem->deleteDirectory(base_path('tests'));

        // Create tests folder
        $filesystem->makeDirectory(base_path('tests'));
        file_put_contents(base_path('tests/TestCase.php'), '<?php ');

        // Cleanup Composer
        $composer = new Composer();
        $composer->set('repositories', []);
        $composer->set('minimum-stability', 'stable');
        $composer->set(
            'require', [
            'laravel/framework' => $composer->get('require.laravel/framework') ?: '^13.0',
            ]
        );
        $composer->save();
        $filesystem->delete(base_path('composer.lock'));

        // Cleanup vendor
        // Cleanup vendor
        $filesystem->deleteDirectory(base_path('vendor'));
        $filesystem->makeDirectory(base_path('vendor'));

        // Register Composer runner mock to handle package installation/updates offline without Satis/network
        Hooks::setComposerRunner(function (array $input, Hooks $hooksInstance) use ($filesystem) {
            $ensureDir = function (string $dir) use ($filesystem) {
                if (!$filesystem->isDirectory($dir)) {
                    $filesystem->makeDirectory($dir, 0755, true);
                }
            };

            $command = $input['command'] ?? null;

            if ($command === 'require') {
                $packages = [];
                foreach ($input as $k => $v) {
                    if (is_int($k) && is_string($v) && !str_starts_with($v, '-')) {
                        $packages[] = $v;
                    } elseif ($k === 'packages' && is_array($v)) {
                        $packages = array_merge($packages, $v);
                    }
                }

                foreach ($packages as $pkgStr) {
                    $parts = explode(':', $pkgStr, 2);
                    $name = $parts[0];
                    $version = $parts[1] ?? null;

                    if ($name === 'local-test-hook') {
                        $source = base_path('hooks/local-test-hook');
                        $dest = base_path('vendor/local-test-hook');
                        if ($filesystem->isDirectory($source)) {
                            $filesystem->copyDirectory($source, $dest);
                        }
                    } elseif ($name === 'composer-github-hook') {
                        if ($version === null || $version === '*') {
                            $version = 'v1.0.1';
                        }
                        $targetDir = base_path('vendor/composer-github-hook');
                        $ensureDir($targetDir.'/src');
                        $filesystem->put($targetDir.'/version', $version);
                        $filesystem->put(
                            $targetDir.'/composer.json',
                            json_encode([
                                'name' => 'composer-github-hook',
                                'description' => 'This is a hook',
                                'require' => [
                                    'marktopper/composer-hook-dependency-1' => '^1.0',
                                ],
                                'extra' => [
                                    'hook' => [
                                        'providers' => [
                                            'ComposerGithubHook\\ComposerGithubHookServiceProvider',
                                        ],
                                        'aliases' => [
                                            'Test' => 'ComposerGithubHook\\TestAlias',
                                        ],
                                    ],
                                ],
                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );
                        $filesystem->put(
                            $targetDir.'/src/ComposerGithubHookServiceProvider.php',
                            '<?php
namespace ComposerGithubHook;
use Illuminate\Support\ServiceProvider;
class ComposerGithubHookServiceProvider extends ServiceProvider {
    public static $isBooted = false;
    public static $isRegistered = false;
    public function register(): void { static::$isRegistered = true; }
    public function boot(): void { static::$isBooted = true; }
}'
                        );
                        $filesystem->put(
                            $targetDir.'/src/TestAlias.php',
                            '<?php
namespace ComposerGithubHook;
class TestAlias {
    public static function foo() { return "bar"; }
}'
                        );
                        $ensureDir(base_path('vendor/marktopper/composer-hook-dependency-1'));
                        if ($version === 'v1.0.1') {
                            $ensureDir(base_path('vendor/marktopper/composer-hook-dependency-2'));
                        } else {
                            $filesystem->deleteDirectory(base_path('vendor/marktopper/composer-hook-dependency-2'));
                        }

                        $lock = [];
                        if ($filesystem->exists(base_path('composer.lock'))) {
                            $lock = json_decode($filesystem->get(base_path('composer.lock')), true) ?: [];
                        }
                        $pkgs = \Illuminate\Support\Arr::get($lock, 'packages', []);
                        $pkgs = array_values(array_filter($pkgs, fn($p) => $p['name'] !== 'composer-github-hook'));
                        $pkgs[] = [
                            'name' => 'composer-github-hook',
                            'version' => $version,
                            'description' => 'This is a hook',
                            'notification-url' => Hooks::getRemote().'/downloads',
                            'require' => [
                                'marktopper/composer-hook-dependency-1' => '^1.0',
                            ],
                            'extra' => [
                                'hook' => [
                                    'providers' => [
                                        'ComposerGithubHook\\ComposerGithubHookServiceProvider',
                                    ],
                                    'aliases' => [
                                        'Test' => 'ComposerGithubHook\\TestAlias',
                                    ],
                                ],
                            ],
                        ];
                        $lock['packages'] = $pkgs;
                        $filesystem->put(base_path('composer.lock'), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    } elseif ($name === 'migrating-hook') {
                        if ($version === null) {
                            $version = 'v1.0.0';
                        } elseif ($version === '*') {
                            $version = 'v2.0.0';
                        }
                        $targetDir = base_path('vendor/migrating-hook');
                        $ensureDir($targetDir.'/resources/database/migrations');
                        $ensureDir($targetDir.'/resources/database/seeders');
                        $ensureDir($targetDir.'/resources/database/unseeders');
                        $ensureDir($targetDir.'/assets');

                        $filesystem->put(
                            $targetDir.'/resources/database/migrations/2018_01_19_000000_create_tests_table.php',
                            '<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
if (!class_exists("CreateTestsTable")) {
    class CreateTestsTable extends Migration {
        public function up() {
            Schema::create("tests", function (Blueprint $table) {
                $table->increments("id");
                $table->string("name")->nullable();
            });
        }
        public function down() {
            Schema::dropIfExists("tests");
        }
    }
}
return new CreateTestsTable();'
                        );

                        if ($version === 'v2.0.0') {
                            $filesystem->put(
                                $targetDir.'/resources/database/migrations/2018_01_19_100000_create_another_tests_table.php',
                                '<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
if (!class_exists("CreateAnotherTestsTable")) {
    class CreateAnotherTestsTable extends Migration {
        public function up() {
            Schema::create("another_tests", function (Blueprint $table) {
                $table->increments("id");
                $table->string("name")->nullable();
            });
        }
        public function down() {
            Schema::dropIfExists("another_tests");
        }
    }
}
return new CreateAnotherTestsTable();'
                            );
                            $filesystem->put(
                                $targetDir.'/resources/database/seeders/TestsTableSeeder.php',
                                '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableSeeder")) {
    class TestsTableSeeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests") && DB::table("tests")->count() === 0) {
                DB::table("tests")->insert([["name" => "1"], ["name" => "2"], ["name" => "3"]]);
            }
            if (Schema::hasTable("another_tests") && DB::table("another_tests")->count() === 0) {
                DB::table("another_tests")->insert([["name" => "1"], ["name" => "2"], ["name" => "3"]]);
            }
        }
    }
}'
                            );
                            $filesystem->put(
                                $targetDir.'/resources/database/unseeders/TestsTableUnseeder.php',
                                '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableUnseeder")) {
    class TestsTableUnseeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests")) {
                DB::table("tests")->truncate();
            }
            if (Schema::hasTable("another_tests")) {
                DB::table("another_tests")->truncate();
            }
        }
    }
}'
                            );
                            $filesystem->put($targetDir.'/assets/script.js', "alert('I am still alive!');\n");
                            $filesystem->put($targetDir.'/assets/another.js', "alert('I am another file!');\n");
                        } else {
                            $filesystem->put(
                                $targetDir.'/resources/database/seeders/TestsTableSeeder.php',
                                '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableSeeder")) {
    class TestsTableSeeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests") && DB::table("tests")->count() === 0) {
                DB::table("tests")->insert([["name" => "1"], ["name" => "2"], ["name" => "3"]]);
            }
            if (Schema::hasTable("another_tests") && DB::table("another_tests")->count() === 0) {
                DB::table("another_tests")->insert([["name" => "1"], ["name" => "2"], ["name" => "3"]]);
            }
        }
    }
}'
                            );
                            $filesystem->put(
                                $targetDir.'/resources/database/unseeders/TestsTableUnseeder.php',
                                '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableUnseeder")) {
    class TestsTableUnseeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests")) {
                DB::table("tests")->truncate();
            }
            if (Schema::hasTable("another_tests")) {
                DB::table("another_tests")->truncate();
            }
        }
    }
}'
                            );
                            $filesystem->put($targetDir.'/assets/script.js', "alert('I am alive!');\n");
                        }

                        $filesystem->put(
                            $targetDir.'/composer.json',
                            json_encode([
                                'name' => 'migrating-hook',
                                'description' => 'This is a migrating hook',
                                'extra' => [
                                    'hook' => [
                                        'migrations' => [
                                            'resources/database/migrations',
                                        ],
                                        'seeders' => [
                                            'resources/database/seeders',
                                        ],
                                        'unseeders' => [
                                            'resources/database/unseeders',
                                        ],
                                        'assets' => [
                                            'assets' => 'public/vendor/migration-hook/assets',
                                        ],
                                    ],
                                ],
                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );

                        $lock = [];
                        if ($filesystem->exists(base_path('composer.lock'))) {
                            $lock = json_decode($filesystem->get(base_path('composer.lock')), true) ?: [];
                        }
                        $pkgs = \Illuminate\Support\Arr::get($lock, 'packages', []);
                        $pkgs = array_values(array_filter($pkgs, fn($p) => $p['name'] !== 'migrating-hook'));
                        $pkgs[] = [
                            'name' => 'migrating-hook',
                            'version' => $version,
                            'description' => 'This is a migrating hook',
                            'notification-url' => Hooks::getRemote().'/downloads',
                            'extra' => [
                                'hook' => [
                                    'migrations' => [
                                        'resources/database/migrations',
                                    ],
                                    'seeders' => [
                                        'resources/database/seeders',
                                    ],
                                    'unseeders' => [
                                        'resources/database/unseeders',
                                    ],
                                    'assets' => [
                                        'assets' => 'public/vendor/migration-hook/assets',
                                    ],
                                ],
                            ],
                        ];
                        $lock['packages'] = $pkgs;
                        $filesystem->put(base_path('composer.lock'), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    } elseif ($name === 'single-migrating-hook') {
                        if ($version === null) {
                            $version = 'v1.0.0';
                        }
                        $targetDir = base_path('vendor/single-migrating-hook');
                        $ensureDir($targetDir.'/resources/database/migrations');
                        $ensureDir($targetDir.'/resources/database/seeders');
                        $ensureDir($targetDir.'/resources/database/unseeders');
                        $ensureDir($targetDir.'/assets/scripts');

                        $filesystem->put(
                            $targetDir.'/resources/database/migrations/2018_01_21_000000_create_tests_table.php',
                            '<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
if (!class_exists("CreateTestsTable")) {
    class CreateTestsTable extends Migration {
        public function up() {
            Schema::create("tests", function (Blueprint $table) {
                $table->increments("id");
                $table->string("name")->nullable();
            });
        }
        public function down() {
            Schema::dropIfExists("tests");
        }
    }
}
return new CreateTestsTable();'
                        );
                        $filesystem->put(
                            $targetDir.'/resources/database/migrations/2018_01_21_000000_create_another_tests_table.php',
                            '<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
if (!class_exists("CreateAnotherTestsTable")) {
    class CreateAnotherTestsTable extends Migration {
        public function up() {
            Schema::create("another_tests", function (Blueprint $table) {
                $table->increments("id");
                $table->string("name")->nullable();
            });
        }
        public function down() {
            Schema::dropIfExists("another_tests");
        }
    }
}
return new CreateAnotherTestsTable();'
                        );
                        $filesystem->put(
                            $targetDir.'/resources/database/seeders/TestsTableSeeder.php',
                            '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableSeeder")) {
    class TestsTableSeeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests") && DB::table("tests")->count() === 0) {
                DB::table("tests")->insert([["name" => "1"], ["name" => "2"], ["name" => "3"]]);
            }
        }
    }
}'
                        );
                        $filesystem->put(
                            $targetDir.'/resources/database/unseeders/TestsTableUnseeder.php',
                            '<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
if (!class_exists("TestsTableUnseeder")) {
    class TestsTableUnseeder extends Seeder {
        public function run(): void {
            if (Schema::hasTable("tests")) {
                DB::table("tests")->truncate();
            }
        }
    }
}'
                        );
                        $filesystem->put($targetDir.'/assets/scripts/alert.js', "alert('Test!');\n");

                        $filesystem->put(
                            $targetDir.'/composer.json',
                            json_encode([
                                'name' => 'single-migrating-hook',
                                'description' => 'This is a single migrating hook',
                                'extra' => [
                                    'hook' => [
                                        'migrations' => [
                                            'resources/database/migrations/2018_01_21_000000_create_tests_table.php',
                                        ],
                                        'seeders' => [
                                            'resources/database/seeders/TestsTableSeeder.php',
                                        ],
                                        'unseeders' => [
                                            'resources/database/unseeders/TestsTableUnseeder.php',
                                        ],
                                        'assets' => [
                                            'assets/scripts/alert.js' => 'public/vendor/single-migrating-hook/assets/scripts/alert.js',
                                        ],
                                    ],
                                ],
                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );

                        $lock = [];
                        if ($filesystem->exists(base_path('composer.lock'))) {
                            $lock = json_decode($filesystem->get(base_path('composer.lock')), true) ?: [];
                        }
                        $pkgs = \Illuminate\Support\Arr::get($lock, 'packages', []);
                        $pkgs = array_values(array_filter($pkgs, fn($p) => $p['name'] !== 'single-migrating-hook'));
                        $pkgs[] = [
                            'name' => 'single-migrating-hook',
                            'version' => $version,
                            'description' => 'This is a single migrating hook',
                            'notification-url' => Hooks::getRemote().'/downloads',
                            'extra' => [
                                'hook' => [
                                    'migrations' => [
                                        'resources/database/migrations/2018_01_21_000000_create_tests_table.php',
                                    ],
                                    'seeders' => [
                                        'resources/database/seeders/TestsTableSeeder.php',
                                    ],
                                    'unseeders' => [
                                        'resources/database/unseeders/TestsTableUnseeder.php',
                                    ],
                                    'assets' => [
                                        'assets/scripts/alert.js' => 'public/vendor/single-migrating-hook/assets/scripts/alert.js',
                                    ],
                                ],
                            ],
                        ];
                        $lock['packages'] = $pkgs;
                        $filesystem->put(base_path('composer.lock'), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }

                return '';
            }

            if ($command === 'remove') {
                $packages = (array) ($input['packages'] ?? []);
                foreach ($packages as $pkg) {
                    $filesystem->deleteDirectory(base_path("vendor/{$pkg}"));
                    if ($filesystem->exists(base_path('composer.lock'))) {
                        $lock = json_decode($filesystem->get(base_path('composer.lock')), true) ?: [];
                        if (isset($lock['packages'])) {
                            $lock['packages'] = array_values(array_filter($lock['packages'], fn($p) => $p['name'] !== $pkg));
                            $filesystem->put(base_path('composer.lock'), json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        }
                    }
                }

                return '';
            }

            if ($command === 'outdated') {
                return json_encode([
                    'installed' => [
                        [
                            'name' => 'composer-github-hook',
                            'version' => 'v1.0.0',
                            'latest' => 'v1.0.1',
                        ],
                    ],
                ]);
            }

            return '';
        });

        // Setup Hook repository
        $this->artisan(
            'hook:setup', [
            '--url' => env('HOOKS_COMPOSER_REPOSITORY', static::COMPOSER_REPOSITORY),
            ]
        );

        // Reload JSON files
        app(Hooks::class)->readJsonFile();

        // Migrate
        $this->artisan('migrate');
    }

    public function tearDown(): void
    {
        Hooks::setComposerRunner(null);

        // Cleanup old hooks before testing
        app(Filesystem::class)->deleteDirectory(base_path('hooks'));

        parent::tearDown();
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set(
            'database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            ]
        );
    }
}
