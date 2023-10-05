<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\Label;

use SilvertipSoftware\LaravelSupport\Blade\Tags\Translator;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

class LabelBuilder {

    public function __construct(
        protected string $templateObject,
        protected string $objectName,
        protected string $methodName,
        public object|null $object,
        protected mixed $tagValue
    ) {
    }

    public function translation(): string {
        $methodAndValue = !empty($this->tagValue)
            ? $this->methodName . '.' . $this->tagValue
            : $this->methodName;

        $translator = new Translator($this->object, $this->objectName, $methodAndValue, 'helpers.label');

        return $translator->translate() ?: StrUtils::humanize($this->methodName);
    }

    public function __toString(): string {
        return $this->translation();
    }
}
