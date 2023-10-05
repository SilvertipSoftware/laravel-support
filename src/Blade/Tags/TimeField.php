<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class TimeField extends DatetimeField {

    protected bool $includeSeconds;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(?string $objectName, string $methodName, string $templateObject, array $options = []) {
        $this->includeSeconds = (bool) Arr::pull($options, 'include_seconds', true);
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    protected function dateFormat(): string {
        return $this->includeSeconds ? 'H:i:s' : 'H:i';
    }
}
