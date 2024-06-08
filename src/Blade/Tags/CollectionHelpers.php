<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Closure;
use Generator;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\Builder as CollectionBuilder;
use Stringable;

trait CollectionHelpers {

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param array<string,mixed> $options
     * @param array<string,mixed> $htmlOptions
     */
    public function __construct(
        ?string $objectName,
        string $methodName,
        string $templateObject,
        protected array|Collection|QueryBuilder|null $collection,
        protected string|int|Closure $valueMethod,
        protected string|int|Closure $textMethod,
        array $options,
        protected array $htmlOptions
    ) {
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    /**
     * @return array<string,mixed>
     */
    protected function defaultHtmlOptionsForCollection(mixed $item, mixed $value): array {
        $htmlOptions = $this->htmlOptions;

        foreach (['checked', 'selected', 'disabled', 'readonly'] as $option) {
            $currentValue = Arr::get($htmlOptions, $option);
            if ($currentValue === null) {
                continue;
            }

            // @phpstan-ignore-next-line
            $accept = $currentValue instanceof Closure
                ? $currentValue($item)
                // @phpstan-ignore-next-line
                : collect($currentValue)->contains($value);

            if ($accept) {
                $htmlOptions[$option] = true;
            } elseif ($option === 'checked') {
                $htmlOptions[$option] = false;
            }
        }

        $htmlOptions['object'] = $this->object;

        return $htmlOptions;
    }

    protected function hiddenField(): HtmlString {
        $hiddenName = Arr::get($this->htmlOptions, 'name') ?: $this->hiddenFieldName();
        return ($this->templateObject)::hiddenFieldTag($hiddenName, '', ['id' => null]);
    }

    protected function hiddenFieldName(): ?string {
        return $this->tagName(false, Arr::get($this->options, 'index'));
    }

    /**
     * @template T of CollectionBuilder
     * @param class-string<T> $builderClass
     * @param array<string,mixed> $htmlOptions
     * @return T
     */
    protected function instantiateBuilder(
        string $builderClass,
        mixed $item,
        mixed $value,
        mixed $text,
        array $htmlOptions
    ) {
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

    /**
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    protected function yieldingRenderCollection(): Generator {
        $pieces = [];
        $collection = $this->collection;

        if ($collection instanceof QueryBuilder) {
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

    /**
     * @param class-string<CollectionBuilder> $builderClass
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    protected function yieldingRenderCollectionFor(string $builderClass, bool $yield): Generator {
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
                // @phpstan-ignore-next-line
                $obj->content = $this->renderComponent($builder);
            }
        }
        $renderedCollection = $generator->getReturn();

        if (Arr::get($options, 'include_hidden', true)) {
            return new HtmlString($this->hiddenField() . $renderedCollection);
        }

        return $renderedCollection;
    }

    protected function sanitizeAttributeName(string|bool|int|float|Stringable|null $value): string {
        return $this->sanitizedMethodName() . '_' . $this->sanitizedValue($value);
    }
}
