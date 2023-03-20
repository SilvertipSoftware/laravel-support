<?php

namespace App\Models;

use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;

class Device extends FluentModel {

    protected function getVendorAttribute() {
        return 'Apple';
    }

    protected function setTypeAttribute($value) {
        $this->attributes['model'] = $value;
    }
}
