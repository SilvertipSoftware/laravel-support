<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class MonthField extends DatetimeField {

    protected function dateFormat(): string {
        return 'Y-m';
    }
}
