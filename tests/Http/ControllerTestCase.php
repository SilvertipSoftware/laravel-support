<?php

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use SilvertipSoftware\LaravelSupport\Providers\LaravelSupportProvider;

require_once __DIR__ . '/../controllers/PackagedControllers.php';
require_once __DIR__ . '/../controllers/TestControllers.php';
require_once __DIR__ . '/../controllers/SubNamespaceControllers.php';

class ControllerTestCase extends TestCase {

    public function setUp(): void {
        parent::setUp();

        View::addLocation(__DIR__ . '/../views');
        View::addNamespace('some_vendor', __DIR__ . '/../views/packaged_views');

        Route::group(['middleware' => ['formats', 'freshness']], function () {
            Route::resource('eyes', 'App\Http\Controllers\EyesController');
            Route::resource('addresses', 'App\Http\Controllers\Company\AddressesController');
            Route::resource('pirates', 'SomeVendor\SomePackage\Controllers\PiratesController');

            Route::get('/times/fresh', ['uses' => 'App\Http\Controllers\TimesController@fresh']);
            Route::get('/times/stale', ['uses' => 'App\Http\Controllers\TimesController@stale']);
        });
    }

    protected function getPackageProviders($app) {
        return [
            LaravelSupportProvider::class
        ];
    }

    protected function acceptHtmlHeaders() {
        return [];
    }

    protected function acceptJavascriptHeaders() {
        return [
            'Accept' => 'text/javascript'
        ];
    }

    protected function acceptStreamHeaders() {
        return [
            'Accept' => 'text/vnd.turbo-stream.html'
        ];
    }
}
