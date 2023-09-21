<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class Builder {

    public function __construct(
        protected $templateObject,
        protected $objectName,
        protected $methodName,
        public $object,
        protected $sanitizedAttributeName,
        public $text,
        public $value,
        protected $inputHtmlOptions
    ) {
    }

    public function label(array $labelHtmlOptions = [], ?callable $block = null): HtmlString {
        $yield = $block != null;
        $generator = $this->yieldingLabel($labelHtmlOptions, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    public function yieldingLabel(array $labelHtmlOptions = [], bool $yield = true): Generator {
        $htmlOptions = array_merge(
            Arr::only($this->inputHtmlOptions, ['index', 'namespace']),
            $labelHtmlOptions
        );

        if (!Arr::get($htmlOptions, 'for') && Arr::get($this->inputHtmlOptions, 'id')) {
            $htmlOptions['for'] = Arr::get($this->inputHtmlOptions, 'id');
        }

        $generator = ($this->templateObject)::yieldingLabel(
            $this->objectName,
            $this->sanitizedAttributeName,
            $this->text,
            $htmlOptions,
            $yield
        );
        yield from $generator;

        return $generator->getReturn();
    }
}
