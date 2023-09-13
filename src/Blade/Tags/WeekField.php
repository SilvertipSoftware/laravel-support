<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class WeekField extends DatetimeField {

    protected function dateFormat() {
        return 'o-\WW';
    }
}
