<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class TextArea extends Base {
    use Placeholderable;

    public function render() {
        $options = $this->options;
        $this->addDefaultNameAndId($options);

        $size = Arr::pull($options, 'size');
        if (is_string($size)) {
            list($options['cols'], $options['rows']) = explode('x', $size);
        }

        $value = Arr::pull($options, 'value', $this->valueBeforeTypeCast());

        return static::contentTag('textarea', $value, $options);
    }
}
