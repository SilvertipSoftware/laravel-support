<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

trait Checkable {

    public function isInputChecked(&$options) {
        if (Arr::has($options, 'checked')) {
            $checked = Arr::pull($options, 'checked');
            return $checked === true || $checked === 'checked';
        }

        return $this->isChecked($this->value());
    }
}
