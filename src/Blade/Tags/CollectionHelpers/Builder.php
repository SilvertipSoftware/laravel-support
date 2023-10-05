<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Blade\Tags\Label\LabelBuilder;

class Builder {

    /**
     * @param array<string,mixed> $inputHtmlOptions
     */
    public function __construct(
        protected string $templateObject,
        protected ?string $objectName,
        protected string $methodName,
        public mixed $object,
        protected string $sanitizedAttributeName,
        public mixed $text,
        public mixed $value,
        protected array $inputHtmlOptions
    ) {
    }

    /**
     * @param array<string,mixed> $labelHtmlOptions
     */
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

    /**
     * @param array<string,mixed> $labelHtmlOptions
     * @return Generator<int,\stdClass,null,HtmlString>
     */
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
