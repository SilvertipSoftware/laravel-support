<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

use Illuminate\Support\Arr;

class Builder {

    public $object;
    public $text;
    public $value;

    protected $inputHtmlOptions;
    protected $methodName;
    protected $objectName;
    protected $sanitizedAttributeName;
    protected $templateObject;

    public function __construct(
        $templateObject,
        $objectName,
        $methodName,
        $object,
        $sanitizedAttributeName,
        $text,
        $value,
        $inputHtmlOptions
    ) {
        $this->templateObject = $templateObject;
        $this->objectName = $objectName;
        $this->methodName = $methodName;
        $this->object = $object;
        $this->sanitizedAttributeName = $sanitizedAttributeName;
        $this->text = $text;
        $this->value = $value;
        $this->inputHtmlOptions = $inputHtmlOptions;
    }

    public function label($labelHtmlOptions = [], $block = null) {
        $htmlOptions = array_merge(Arr::only($this->inputHtmlOptions, ['index', 'namespace']), $labelHtmlOptions);
        if (!Arr::get($htmlOptions, 'for') && Arr::get($this->inputHtmlOptions, 'id')) {
            $htmlOptions['for'] = Arr::get($this->inputHtmlOptions, 'id');
        }

        return ($this->templateObject)::label(
            $this->objectName,
            $this->sanitizedAttributeName,
            $this->text,
            $htmlOptions,
            $block
        );
    }
}
