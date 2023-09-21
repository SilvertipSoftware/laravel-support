<?php

namespace Tests\Blade\Directives;

require_once __DIR__ . '/../../models/TestFormModels.php';

use App\Models\Car;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use SilvertipSoftware\LaravelSupport\Blade\ViewSupport;

trait DirectiveTestSupport {

    protected $posts;
    protected $comments;
    protected $newPost;
    protected $existingPost;

    protected function assertBlade($expected, $str, $data = null, $stripNewLines = true) {
        $this->assertDomEquals($expected, $this->blade($str, $data), $stripNewLines);
    }

    protected function assertDirectiveExists($name) {
        $this->assertArrayHasKey($name, Blade::getCustomDirectives());

        return $this;
    }

    protected function assertDirectiveNotExists($name) {
        $this->assertArrayNotHasKey($name, Blade::getCustomDirectives());

        return $this;
    }

    protected function blade($str, $data = null) {
        return Blade::render($str, $data ?: $this->standardData());
    }

    protected function createFixtures() {
        View::addLocation(__DIR__ . '/../../views');

        ViewSupport::registerDirectives();

        Route::resource('posts', '');
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('posts', '');
        });

        $this->posts = collect([
            new Post(['id' => 1, 'title' => 'Hello World']),
            new Post(['id' => 2, 'title' => 'This is a post'])
        ]);

        $this->comments = collect([
            new Comment(['body' => 'First comment!']),
            new Comment(['body' => 'Great post'])
        ]);

        $this->newPost = new Post(['title' => 'Draft']);
        $this->existingPost = new Post(['id' => 3, 'title' => 'Saved Already', 'body' => 'This post exists']);
        $this->existingPost->exists = true;

        $this->car = new Car(['vin' => 'H1234567890', 'make' => 'Honda', 'model' => 'Civic']);
    }

    protected function getEnvironmentSetUp($app) {
        $app['config']->set('view.paths', ['tests/views']);
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function rendered($name, $data = null) {
        return view($name, $data ?: $this->standardData())->render();
    }

    protected function standardData() {
        return [
            'car' => $this->car,
            'posts' => $this->posts,
            'newPost' => $this->newPost,
            'existingPost' => $this->existingPost
        ];
    }
}
