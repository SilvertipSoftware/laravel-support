<?php

namespace SilvertipSoftware\LaravelSupport;

use Illuminate\Contracts\Foundation\Application;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;

class CurrentAttributes extends FluentModel {

    public function __construct(protected Application $app) {
    }
}
