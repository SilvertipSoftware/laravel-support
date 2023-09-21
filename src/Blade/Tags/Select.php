<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class Select extends Base {

    protected $choices;

    public function __construct(
        $objectName,
        $methodName,
        $templateObject,
        $choices,
        $options,
        $htmlOptions,
        $block = null
    ) {
        if ($block) {
            $choices = $block();
        }

        if ($choices instanceof HtmlString) {
            $this->choices = $choices->toHtml();
        } elseif (is_string($choices)) {
            $this->choices = e($choices);
        } else {
            $this->choices = $choices;
        }

        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render() {
        $optionTagsOptions = [
            'selected' => Arr::get($this->options, 'selected', $this->value() === null ? '' : $this->value()),
            'disabled' => Arr::get($this->options, 'disabled')
        ];

        $optionTags = $this->areChoicesGrouped()
            ? static::groupedOptionsForSelect($this->choices, $optionTagsOptions)
            : static::optionsForSelect($this->choices, $optionTagsOptions);

        return $this->selectContentTag($optionTags, $this->options, $this->htmlOptions);
    }

    protected function areChoicesGrouped() {
        if (empty($this->choices)) {
            return false;
        }

        $choiceCollection = collect($this->choices);
        if ($choiceCollection->count() == 0) {
            return false;
        }

        $keys = $choiceCollection->keys()->all();
        $firstKey = $keys[0];
        $firstValue = $choiceCollection->get($firstKey);

        if (!is_int($firstKey)) {
            return is_array($firstValue);
        } else {
            if (is_array($firstValue) && count($firstValue) > 1 && is_array($firstValue[1])) {
                return true;
            }
        }

        return false;
    }
}
