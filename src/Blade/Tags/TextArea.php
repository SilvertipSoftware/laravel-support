<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class TextArea extends Base {
    use Placeholderable;

    public function render(): HtmlString {
        $options = $this->options;
        $this->addDefaultNameAndId($options);

        $size = Arr::pull($options, 'size');
        if (is_string($size)) {
            list($options['cols'], $options['rows']) = explode('x', $size);
        }

        $value = Arr::pull($options, 'value', $this->valueBeforeTypeCast());

        return $this->instanceContentTag('textarea', $value, $options);
    }
}
