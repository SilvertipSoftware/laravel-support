<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\URL;

trait Routing {

    protected function url(mixed ...$args): string {
        // @phpstan-ignore-next-line
        return URL::url(...$args);
    }

    protected function path(mixed ...$args): string {
        // @phpstan-ignore-next-line
        return URL::path(...$args);
    }

    protected function redirect(mixed ...$args): Redirector|RedirectResponse {
        return redirect($this->url(...$args), 303);
    }
}
