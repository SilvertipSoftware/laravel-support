<?php

use App\Models\Eye;
use App\Models\Post\TrackBack;
use Illuminate\Support\Facades\Route;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;

require_once __DIR__ . '/../Http/ControllerTestCase.php';
require_once __DIR__ . '/../models/Post/TrackBack.php';
require_once __DIR__ . '/../models/TestModels.php';

class RestRouterTest extends ControllerTestCase {

    public function setUp() {
        parent::setUp();

        Route::get('/hello', ['as' => 'hellos.index']);
        Route::get('/edithello', ['as' => 'hellos.edit']);
        Route::get('/createhello', ['as' => 'hellos.create']);
        Route::group(['prefix' => 'admin'], function () {
            Route::resource('eyes', 'App\Http\Controllers\EyesController', ['as' => 'admin']);
        });

        Route::resource('track-backs', 'App\Http\Controllers\EyesController', ['only' => 'index']);
    }

    public function testSingleString() {
        $url = RestRouter::url('hello');
        $path = RestRouter::path('hello');

        $this->assertEquals('http://localhost/hello', $url);
        $this->assertEquals('/hello', $path);
    }

    public function testSingleStringWithParams() {
        $url = RestRouter::url('hello', ['fmt' => 'pdf']);
        $path = RestRouter::path('hello', ['fmt' => 'pdf']);

        $this->assertEquals('http://localhost/hello?fmt=pdf', $url);
        $this->assertEquals('/hello?fmt=pdf', $path);
    }

    public function testSingleStringWithActionParam() {
        $url = RestRouter::url('hello', ['action' => 'edit']);
        $path = RestRouter::path('hello', ['action' => 'edit']);

        $this->assertEquals('http://localhost/edithello', $url);
        $this->assertEquals('/edithello', $path);
    }

    public function testSingleStringWithSpecialActionAsLastArg() {
        foreach (['edit', 'create'] as $action) {
            $url = RestRouter::url('hello', $action);
            $path = RestRouter::path('hello', $action);

            $this->assertEquals('http://localhost/' . $action . 'hello', $url);
            $this->assertEquals('/' . $action . 'hello', $path);
        }
    }

    public function testSingleStringWithRegularActionAsLastArg() {
        $this->expectException(Exception::class);

        $url = RestRouter::url('hello', 'index');
    }

    public function testSingleModelName() {
        $url = RestRouter::url(Eye::class);
        $path = RestRouter::path(Eye::class);

        $this->assertEquals('http://localhost/eyes', $url);
        $this->assertEquals('/eyes', $path);
    }

    public function testSingleModelNameWithAction() {
        $url = RestRouter::url(Eye::class, 'create');
        $path = RestRouter::path(Eye::class, 'create');

        $this->assertEquals('http://localhost/eyes/create', $url);
        $this->assertEquals('/eyes/create', $path);
    }

    public function testSingleNewModel() {
        $eye = new Eye();

        $url = RestRouter::url($eye);
        $path = RestRouter::path($eye);

        $this->assertEquals('http://localhost/eyes', $url);
        $this->assertEquals('/eyes', $path);
    }

    public function testSingleExistingModel() {
        $eye = new Eye(['id' => 1234]);
        $eye->exists = true;

        $url = RestRouter::url($eye);
        $path = RestRouter::path($eye);

        $this->assertEquals('http://localhost/eyes/1234', $url);
        $this->assertEquals('/eyes/1234', $path);
    }

    public function testSingleExistingModelWithAction() {
        $eye = new Eye(['id' => 1234]);
        $eye->exists = true;

        $url = RestRouter::url($eye, ['action' => 'edit']);
        $path = RestRouter::path($eye, ['action' => 'update']);

        $this->assertEquals('http://localhost/eyes/1234/edit', $url);
        $this->assertEquals('/eyes/1234', $path);
    }

    public function testSingleExistingModelWithDestroyAction() {
        $eye = new Eye(['id' => 1234]);
        $eye->exists = true;

        $path = RestRouter::path($eye, ['action' => 'destroy']);

        $this->assertEquals('/eyes/1234', $path);
    }

    public function testPrefixedModel() {
        $eye = new Eye(['id' => 1234]);
        $eye->exists = true;

        $url = RestRouter::url('admin', $eye);
        $path = RestRouter::path('admin', $eye, ['action' => 'edit']);

        $this->assertEquals('http://localhost/admin/eyes/1234', $url);
        $this->assertEquals('/admin/eyes/1234/edit', $path);
    }

    public function testNamespacedModelClass() {
        $path = RestRouter::path(TrackBack::class);

        $this->assertEquals('/track-backs', $path);
    }

    public function testNoDefinedRoute() {
        $this->expectException(Exception::class);

        $trackBack = new TrackBack(['id' => 1234]);
        $trackBack->exists = true;

        RestRouter::path($trackBack);
    }
}
