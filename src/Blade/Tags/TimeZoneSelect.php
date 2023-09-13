<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class TimeZoneSelect extends Base {

    protected $htmlOptions;
    protected $priorityZones;

    public function __construct($objectName, $methodName, $templateObject, $priorityZones, $options, $htmlOptions) {
        $this->priorityZones = $priorityZones;
        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render() {
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
