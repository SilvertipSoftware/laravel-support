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
