<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class WeekField extends DatetimeField {

    protected function dateFormat(): string {
        return 'o-\WW';
    }
}
