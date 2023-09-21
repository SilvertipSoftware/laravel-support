<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
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
        }

        $htmlOptions['object'] = $this->object;

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

    protected function yieldingRenderCollection() {
        $pieces = [];
        $collection = $this->collection;

        if ($collection instanceof Builder || $collection instanceof EloquentBuilder) {
            $collection = $collection->get();
        }

        foreach (collect($collection) as $key => $item) {
            $value = static::valueForCollection($item, $this->valueMethod, $key);
            $text = static::valueForCollection($item, $this->textMethod, $key);
            $defaultHtmlOptions = $this->defaultHtmlOptionsForCollection($item, $value);
            $additionalHtmlOptions = static::optionHtmlAttributes($item);

            $obj = (object)[
                'item' => $item,
                'value' => $value,
                'text' => $text,
                'defaultHtmlOptions' => array_merge($defaultHtmlOptions, $additionalHtmlOptions),
                'content' => ''
            ];
            yield $obj;
            $pieces[] = $obj->content;
        }

        return new HtmlString(implode('', $pieces));
    }

    protected function yieldingRenderCollectionFor($builderClass, $yield) {
        $options = $this->options;

        $generator = $this->yieldingRenderCollection();
        foreach ($generator as $obj) {
            $builder = $this->instantiateBuilder(
                $builderClass,
                $obj->item,
                $obj->value,
                $obj->text,
                $obj->defaultHtmlOptions
            );

            if ($yield) {
                $obj2 = (object)[
                    'builder' => $builder,
                    'content' => ''
                ];
                yield $obj2;
                $obj->content = $obj2->content;
            } else {
                $obj->content = $this->renderComponent($builder);
            }
        }
        $renderedCollection = $generator->getReturn();

        if (Arr::get($options, 'include_hidden', true)) {
            return new HtmlString($this->hiddenField() . $renderedCollection);
        }

        return $renderedCollection;
    }

    protected function sanitizeAttributeName($value) {
        return $this->sanitizedMethodName() . '_' . $this->sanitizedValue($value);
    }
}
