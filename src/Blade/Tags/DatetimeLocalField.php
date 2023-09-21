<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class DatetimeLocalField extends DatetimeField {

    protected function dateFormat() {
        return 'Y-m-d\TH:i:s';
    }
}
