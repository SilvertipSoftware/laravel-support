<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class MonthField extends DatetimeField {

    protected function dateFormat() {
        return 'Y-m';
    }
}
