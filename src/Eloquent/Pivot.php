<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class Pivot extends Model {
    use AsPivot;

    public $incrementing = false;
}
