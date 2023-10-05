<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

trait Checkable {

    /**
     * @param array<string,mixed> $options
     */
    public function isInputChecked(array &$options): bool {
        if (Arr::has($options, 'checked')) {
            $checked = Arr::pull($options, 'checked');
            return $checked === true || $checked === 'checked';
        }

        return $this->isChecked($this->value());
    }
}
