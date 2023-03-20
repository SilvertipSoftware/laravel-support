<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use SilvertipSoftware\LaravelSupport\Http\Permitter;

trait StrongParameters {

    protected function require($keys) {
        $keys = (array) $keys;

        $topRules = array_reduce($keys, function ($memo, $key) {
            $memo[$key] = 'required';

            return $memo;
        }, []);

        $filtered = $this->validate(request(), $topRules);

        return new Permitter($keys, $filtered, $this);
    }
}
