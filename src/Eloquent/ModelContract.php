<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;

interface ModelContract {

    public static function modelName(): Name;
    public function getModelNameAttribute(): Name;
}
