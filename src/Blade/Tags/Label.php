<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Blade\Tags\Label\LabelBuilder;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;
use Stringable;

class Label extends Base {

    protected string|Stringable|null $content;

    /**
     * @param string|Stringable|array<string,mixed>|null $contentOrOptions
     * @param array<string,mixed> $options
     */
    public function __construct(
        ?string $objectName,
        string $methodName,
        string $templateObject,
        string|Stringable|array|null $contentOrOptions = null,
        ?array $options = []
    ) {
        $options = $options ?? [];
        if (is_array($contentOrOptions)) {
            $options = array_merge($options, $contentOrOptions);
            $this->content = null;
        } else {
            $this->content = $contentOrOptions;
        }

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    /**
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public function yieldingRender(bool $yield = true): Generator {
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
        if ($yield) {
            $obj = (object)[
                'builder' => $builder,
                'content' => ''
            ];
            yield $obj;
            $content = $obj->content;
        } elseif ($this->content) {
            $content = $this->content;
        } else {
            $content = $this->renderComponent($builder);
        }

        return $this->instanceLabelTag($nameAndId['id'], $content, $options);
    }

    private function makeLabelBuilder(mixed $tagValue): object {
        return new LabelBuilder($this->templateObject, $this->objectName, $this->methodName, $this->object, $tagValue);
    }

    private function renderComponent(LabelBuilder $builder): string {
        return $builder->translation();
    }
}
