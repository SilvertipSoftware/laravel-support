<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class CollectionSelect extends Base {

    protected $collection;
    protected $valueMethod;
    protected $textMethod;
    protected $htmlOptions;

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

    public function render() {
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
