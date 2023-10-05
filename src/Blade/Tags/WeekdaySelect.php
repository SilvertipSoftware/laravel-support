<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class WeekdaySelect extends Base {

    /**
     * @param array<string,mixed> $htmlOptions
     */
    public function __construct(
        ?string $objectName,
        string $methodName,
        string $templateObject,
        array $options,
        protected array $htmlOptions
    ) {
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render(): HtmlString {
        return $this->selectContentTag(
            static::weekdayOptionsForSelect(
                $this->value() ?: Arr::get($this->options, 'selected'),
                Arr::get($this->options, 'index_as_value', false),
                Arr::get($this->options, 'day_format', 'day_names'),
                Arr::get($this->options, 'beginning_of_week', 1)
            ),
            $this->options,
            $this->htmlOptions
        );
    }
}
