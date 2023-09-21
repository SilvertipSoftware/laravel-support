<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class GroupedCollectionSelect extends Base {

    protected $collection;
    protected $groupMethod;
    protected $groupLabelMethod;
    protected $htmlOptions;
    protected $optionKeyMethod;
    protected $optionValueMethod;

    public function __construct(
        $objectName,
        $methodName,
        $templateObject,
        $collection,
        $groupMethod,
        $groupLabelMethod,
        $optionKeyMethod,
        $optionValueMethod,
        $options,
        $htmlOptions
    ) {
        $this->collection = $collection;
        $this->groupMethod = $groupMethod;
        $this->groupLabelMethod = $groupLabelMethod;
        $this->optionKeyMethod = $optionKeyMethod;
        $this->optionValueMethod = $optionValueMethod;
        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render() {
        $optionTagsOptions = [
            'selected' => Arr::get($this->options, 'selected', $this->value()),
            'disabled' => Arr::get($this->options, 'disabled')
        ];

        return $this->selectContentTag(
            static::optionGroupsFromCollectionForSelect(
                $this->collection,
                $this->groupMethod,
                $this->groupLabelMethod,
                $this->optionKeyMethod,
                $this->optionValueMethod,
                $optionTagsOptions
            ),
            $this->options,
            $this->htmlOptions
        );
    }
}
