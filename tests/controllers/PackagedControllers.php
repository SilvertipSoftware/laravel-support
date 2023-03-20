<?php


namespace SomeVendor\SomePackage\Controllers;

use SilvertipSoftware\LaravelSupport\Http\Controller;

class PiratesController extends Controller {

    public function index() {
    }

    protected function controllerRootNamespace() {
        return 'SomeVendor\\SomePackage\\Controllers';
    }

    protected function viewNamePrefix() {
        return 'some_vendor::';
    }
}
