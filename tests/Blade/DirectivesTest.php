<?php

namespace Tests\Blade;

use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;
use Tests\TestSupport\HtmlAssertions;

class DirectivesTest extends TestCase {
    use HtmlAssertions;

    public function setUp(): void {
        parent::setUp();
        ViewSupport::registerDirectives();
        $this->posts = collect([
            (object)['id' => 1, 'title' => 'Hello World'],
            (object)['id' => 2, 'title' => 'This is a post']
        ]);
    }

    public function testCompendium() {
        $this->expectNotToPerformAssertions();

        $result = view('compendium', ['posts' => $this->posts])->render();
        echo(preg_replace('/&#10;/', "\n", $result));
    }

    protected function getEnvironmentSetUp($app) {
        $app['config']->set('view.paths', ['tests/views']);
    }
}