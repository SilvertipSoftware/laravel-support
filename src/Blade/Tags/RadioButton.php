<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class RadioButton extends Base {
    use Checkable;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        ?string $objectName,
        string $methodName,
        string $templateObject,
        protected string|bool|int|float|null $tagValue,
        array$options
    ) {
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render(): HtmlString {
        $options = $this->options;
        $options['type'] = 'radio';
        $options['value'] = $this->tagValue;
        if ($this->isInputChecked($options)) {
            $options['checked'] = 'checked';
        }

        $this->addDefaultNameAndIdForValue($this->tagValue, $options);

        return $this->instanceTag('input', $options);
    }

    protected function isChecked(mixed $value): bool {
        return $value == $this->tagValue;
    }
}
