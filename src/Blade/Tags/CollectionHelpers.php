<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait CollectionHelpers {

    public function __construct(
        $objectName,
        $methodName,
        $templateObject,
        $collection,
        $valueMethod,
        $textMethod,
        $options,
        $htmlOptions
    ) {
        $this->collection = $collection;
        $this->valueMethod = $valueMethod;
        $this->textMethod = $textMethod;
        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    protected function defaultHtmlOptionsForCollection($item, $value) {
        $htmlOptions = $this->htmlOptions;

        foreach (['checked', 'selected', 'disabled', 'readonly'] as $option) {
            $currentValue = Arr::get($htmlOptions, $option);
            if ($currentValue === null) {
                continue;
            }

            $accept = $currentValue instanceof Closure
                ? $currentValue($item)
                : in_array($value, (array)$currentValue);

            if ($accept) {
                $htmlOptions[$option] = true;
            } elseif ($option === 'checked') {
                $htmlOptions[$option] = false;
            }

            $htmlOptions['object'] = $this->object;

            return $htmlOptions;
        }

        return $htmlOptions;
    }

    protected function hiddenField() {
        $hiddenName = Arr::get($this->htmlOptions, 'name') ?: $this->hiddenFieldName();
        return ($this->templateObject)::hiddenFieldTag($hiddenName, '', ['id' => null]);
    }

    protected function hiddenFieldName() {
        return $this->tagName(false, Arr::get($this->options, 'index'));
    }

    protected function instantiateBuilder($builderClass, $item, $value, $text, $htmlOptions) {
        return new $builderClass(
            $this->templateObject,
            $this->objectName,
            $this->methodName,
            $item,
            $this->sanitizeAttributeName($value),
            $text,
            $value,
            $htmlOptions
        );
    }

    protected function renderCollection($renderFn) {
        $pieces = collect($this->collection)->map(function ($item, $key) use ($renderFn) {
            $value = static::valueForCollection($item, $this->valueMethod, $key);
            $text = static::valueForCollection($item, $this->textMethod, $key);
            $defaultHtmlOptions = $this->defaultHtmlOptionsForCollection($item, $value);
            $additionalHtmlOptions = static::optionHtmlAttributes($item);

            return $renderFn($item, $value, $text, array_merge($defaultHtmlOptions, $additionalHtmlOptions));
        });

        return new HtmlString(implode('', $pieces->all()));
    }

    protected function renderCollectionFor($builderClass, $block) {
        $options = $this->options;

        $renderFn = function ($item, $value, $text, $defaultHtmlOptions) use ($builderClass, $block) {
            $builder = $this->instantiateBuilder($builderClass, $item, $value, $text, $defaultHtmlOptions);

            return $block
                ? $block($builder)
                : $this->renderComponent($builder);
        };

        $renderedCollection = $this->renderCollection($renderFn);

        if (Arr::get($options, 'include_hidden', true)) {
            return new HtmlString($this->hiddenField() . $renderedCollection);
        }

        return $renderedCollection;
    }

    protected function sanitizeAttributeName($value) {
        return $this->sanitizedMethodName() . '_' . $this->sanitizedValue($value);
    }
}
