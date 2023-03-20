<?php

namespace SomeVendor\Package;

use Illuminate\Database\Eloquent\Model;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming;
use SilvertipSoftware\LaravelSupport\Eloquent\Translation;

class Warehouse extends Model {
    use Naming, Translation;

    public static function i18nScope() {
        return 'package::';
    }
}
