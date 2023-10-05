<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class TimeZoneSelect extends Base {

    /**
     * @param string|string[] $priorityZones
     * @param array<string,mixed> $options
     * @param array<string,mixed> $htmlOptions
     */
    public function __construct(
        ?string $objectName,
        string $methodName,
        string $templateObject,
        protected array|string|null $priorityZones,
        array $options,
        protected array $htmlOptions
    ) {
        $this->priorityZones = $priorityZones;
        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render(): HtmlString {
        return $this->selectContentTag(
            static::timeZoneOptionsForSelect(
                $this->value() ?: Arr::get($this->options, 'default'),
                $this->priorityZones,
                Arr::get($this->options, 'model') ?: timezone_identifiers_list()
            ),
            $this->options,
            $this->htmlOptions
        );
    }
}
