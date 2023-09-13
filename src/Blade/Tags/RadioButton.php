<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class RadioButton extends Base {
    use Checkable;

    protected $tagValue;

    public function __construct($objectName, $methodName, $templateObject, $tagValue, $options) {
        $this->tagValue = $tagValue;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render() {
        $options = $this->options;
        $options['type'] = 'radio';
        $options['value'] = $this->tagValue;
        if ($this->isInputChecked($options)) {
            $options['checked'] = 'checked';
        }

        $this->addDefaultNameAndIdForValue($this->tagValue, $options);

        return static::tag('input', $options);
    }

    protected function isChecked($value) {
        return $value == $this->tagValue;
    }
}
