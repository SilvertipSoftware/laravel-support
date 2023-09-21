<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class TimeField extends DatetimeField {

    protected $includeSeconds;

    public function __construct($objectName, $methodName, $templateObject, $options = []) {
        $this->includeSeconds = Arr::pull($options, 'include_seconds', true);
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    protected function dateFormat() {
        return $this->includeSeconds ? 'H:i:s' : 'H:i';
    }
}
