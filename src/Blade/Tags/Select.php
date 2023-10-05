<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Stringable;

/**
 * @phpstan-type OptionHash array<string,mixed>
 */
class Select extends Base {

    /** @var string|Stringable|array<mixed>|Collection|null */
    protected string|Stringable|array|Collection|null $choices;

    /**
     * @param class-string $templateObject
     * @param string|Stringable|array<mixed>|Collection|null $choices
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function __construct(
        string $objectName,
        string $methodName,
        string $templateObject,
        string|Stringable|array|Collection|null $choices,
        array $options,
        array $htmlOptions,
        ?Closure $block = null
    ) {
        if ($block) {
            $choices = $block();
        }

        if ($choices instanceof HtmlString) {
            $this->choices = $choices->toHtml();
        } elseif (is_string($choices)) {
            $this->choices = e($choices);
        } else {
            $this->choices = $choices;
        }

        $this->htmlOptions = $htmlOptions;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render(): HtmlString {
        $optionTagsOptions = [
            'selected' => Arr::get($this->options, 'selected', $this->value() === null ? '' : $this->value()),
            'disabled' => Arr::get($this->options, 'disabled')
        ];

        $optionTags = $this->areChoicesGrouped()
            ? static::groupedOptionsForSelect($this->choices, $optionTagsOptions)
            : static::optionsForSelect($this->choices, $optionTagsOptions);

        return $this->selectContentTag($optionTags, $this->options, $this->htmlOptions);
    }

    protected function areChoicesGrouped(): bool {
        if (empty($this->choices)) {
            return false;
        }

        $choiceCollection = collect($this->choices);
        if ($choiceCollection->count() == 0) {
            return false;
        }

        $keys = $choiceCollection->keys()->all();
        $firstKey = $keys[0];
        $firstValue = $choiceCollection->get($firstKey);

        if (!is_int($firstKey)) {
            return is_array($firstValue);
        } else {
            if (is_array($firstValue) && count($firstValue) > 1 && is_array($firstValue[1])) {
                return true;
            }
        }

        return false;
    }
}
