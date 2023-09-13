<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

class Label extends Base {

    protected $content;

    public function __construct($objectName, $methodName, $templateObject, $contentOrOptions = null, $options = []) {
        $options = $options ?? [];
        if (is_array($contentOrOptions)) {
            $options = array_merge($options, $contentOrOptions);
            $this->content = null;
        } else {
            $this->content = $contentOrOptions;
        }

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render($block = null) {
        $options = $this->options;
        $tagValue = Arr::pull($options, 'value');
        $nameAndId = $options;

        if (Arr::has($nameAndId, 'for')) {
            $nameAndId['id'] = $nameAndId['for'];
        } else {
            unset($nameAndId['id']);
        }

        $this->addDefaultNameAndIdForValue($tagValue, $nameAndId);
        Arr::pull($options, 'index');
        Arr::pull($options, 'namespace');
        if (!Arr::has($options, 'for')) {
            $options['for'] = $nameAndId['id'];
        }

        $builder = $this->makeLabelBuilder($tagValue);
        if ($block) {
            $content = $block($builder);
        } elseif ($this->content) {
            $content = $this->content;
        } else {
            $content = $this->renderComponent($builder);
        }

        return static::labelTag($nameAndId['id'], $content, $options);
    }

    private function makeLabelBuilder($tagValue) {
        return new class($this->templateObject, $this->objectName, $this->methodName, $this->object, $tagValue) {
            public $object;

            protected $templateObject;
            protected $objectName;
            protected $methodName;
            protected $tagValue;

            public function __construct($templateObject, $objectName, $methodName, $object, $tagValue) {
                $this->templateObject = $templateObject;
                $this->objectName = $objectName;
                $this->methodName = $methodName;
                $this->object = $object;
                $this->tagValue = $tagValue;
            }

            public function translation() {
                $methodAndValue = !empty($this->tagValue)
                    ? $this->methodName . '.' . $this->tagValue
                    : $this->methodName;

                $translator = new Translator($this->object, $this->objectName, $methodAndValue, 'helpers.label');

                return $translator->translate() ?: StrUtils::humanize($this->methodName);
            }

            public function __toString() {
                return $this->translation();
            }
        };
    }

    private function renderComponent($builder) {
        return $builder->translation();
    }
}
