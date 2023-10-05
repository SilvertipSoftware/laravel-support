<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

trait HasTraits {

    protected function initializeTraits(): void {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($this, $method = 'initialize' . class_basename($trait))) {
                $this->{$method}();
            }
        }
    }
}
