<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\TestCase;

class DatabaseTestCase extends TestCase {
    use DatabaseMigrations;

    protected $queries;

    public function setUp(): void {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->getConnection()->listen(function ($event) {
            $this->queries[] = $event->sql;
        });

        $this->resetQueries();
    }

    protected function assertQueryCount($num) {
        $this->assertCount($num, $this->queries);
    }

    protected function getApplicationProviders($app) {
        return [
            \Illuminate\Cache\CacheServiceProvider::class,
            \Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
            \Illuminate\Database\DatabaseServiceProvider::class,
            \Illuminate\Filesystem\FilesystemServiceProvider::class,
            \Illuminate\Queue\QueueServiceProvider::class,
            \Illuminate\Translation\TranslationServiceProvider::class,
            \Illuminate\Validation\ValidationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app) {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function resetQueries() {
        $this->queries = [];
    }
}
