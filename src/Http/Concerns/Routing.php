<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Facades\URL;

trait Routing {

    protected function url(...$args) {
        // @phpstan-ignore-next-line
        return URL::url(...$args);
    }

    protected function path(...$args) {
        // @phpstan-ignore-next-line
        return URL::path(...$args);
    }

    protected function redirect(...$args) {
        return redirect($this->url(...$args), 303);
    }
}
