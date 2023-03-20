<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Facades\URL;

trait Routing {

    protected function url(...$args) {
        return URL::url(...$args);
    }

    protected function path(...$args) {
        return URL::path(...$args);
    }

    protected function redirect(...$args) {
        return redirect($this->url(...$args), 303);
    }
}
