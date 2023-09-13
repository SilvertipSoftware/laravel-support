<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class DateField extends DatetimeField {

    protected function dateFormat() {
        return 'Y-m-d';
    }
}
