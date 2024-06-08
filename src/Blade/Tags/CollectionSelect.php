<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Closure;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class CollectionSelect extends Base {

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder $collection
     * @param array<string,mixed> $options
     * @param array<string,mixed> $htmlOptions
     */
    public function __construct(
        string $objectName,
        string $methodName,
        string $templateObject,
        protected array|Collection|QueryBuilder $collection,
        protected string|int|Closure $valueMethod,
        protected string|int|Closure $textMethod,
        array $options,
        protected array $htmlOptions
    ) {
        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render(): HtmlString {
        $optionTagsOptions = [
            'selected' => Arr::get($this->options, 'selected', $this->value()),
            'disabled' => Arr::get($this->options, 'disabled')
        ];

        return $this->selectContentTag(
            static::optionsFromCollectionForSelect(
                $this->collection,
                $this->valueMethod,
                $this->textMethod,
                $optionTagsOptions
            ),
            $this->options,
            $this->htmlOptions
        );
    }
}
