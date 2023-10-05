<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

trait StrongParameters {

    protected function params(): Parameters {
        return new Parameters(request()->all());
    }
}
