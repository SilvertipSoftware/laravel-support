<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

trait StrongParameters {

    protected function params() {
        return new Parameters(request()->all());
    }
}
