<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

trait Placeholderable {

    public function __construct(...$args) {
        parent::__construct(...$args);

        $tagValue = Arr::get($this->options, 'placeholder');
        if ($tagValue !== null) {
            $placeholder = null;
            if (is_string($tagValue)) {
                $placeholder = $tagValue;
            }
            $methodAndValue = $tagValue === true
                ? $this->methodName
                : $this->methodName . '.' . $tagValue;

            if (!$placeholder) {
                $translator = new Translator($this->object, $this->objectName, $methodAndValue, 'helpers.placeholder');
                $placeholder = $translator->translate() ?: StrUtils::humanize($this->methodName);
            }

            $this->options['placeholder'] = $placeholder;
        }
    }
}
