<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class DateField extends DatetimeField {

    protected function dateFormat() {
        return 'Y-m-d';
    }
}
