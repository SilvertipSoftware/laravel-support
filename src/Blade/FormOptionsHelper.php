<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Carbon\Carbon;
use Closure;
use Generator;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;
use Stringable;

/**
 * @phpstan-type HtmlStringGenerator Generator<int,\stdClass,null,HtmlString>
 * @phpstan-type OptionHash array<string,mixed>
 */
trait FormOptionsHelper {
    use TagHelper;

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function collectionCheckBoxes(
        ?string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = static::yieldingCollectionCheckBoxes(
            $object,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions,
            $yield
        );

        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function collectionRadioButtons(
        ?string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        ?Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = static::yieldingCollectionRadioButtons(
            $object,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions,
            $yield
        );

        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function collectionSelect(
        ?string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return (new Tags\CollectionSelect(
            $object,
            $method,
            static::class,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions
        ))->render();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function groupedCollectionSelect(
        ?string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $groupMethod,
        string|int|Closure $groupLabelMethod,
        string|int|Closure $optionKeyMethod,
        string|int|Closure $optionValueMethod,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        $obj = new Tags\GroupedCollectionSelect(
            $object,
            $method,
            static::class,
            $collection,
            $groupMethod,
            $groupLabelMethod,
            $optionKeyMethod,
            $optionValueMethod,
            $options,
            $htmlOptions
        );

        return $obj->render();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed> $groupedOptions
     * @param OptionHash $options
     */
    public static function groupedOptionsForSelect(
        array|Collection $groupedOptions,
        mixed $selectedKey = null,
        array $options = []
    ): HtmlString {
        $prompt = Arr::get($options, 'prompt');
        $divider = Arr::get($options, 'divider');
        $body = '';

        if ($prompt) {
            $body .= static::contentTag('option', static::promptText($prompt), ['value' => '']);
        }

        collect($groupedOptions)->each(function ($container, $key) use (&$body, $divider, $selectedKey) {
            $htmlAttributes = static::optionHtmlAttributes($container);

            if ($divider) {
                $label = $divider;
            } elseif (!is_int($key)) {
                $label = $key;
            } else {
                list($label, $container) = $container;
            }

            $htmlAttributes = array_merge(['label' => $label], $htmlAttributes);
            $body .= static::contentTag(
                'optgroup',
                static::optionsForSelect($container, $selectedKey),
                $htmlAttributes
            );
        });

        return new HtmlString($body);
    }

    /**
     * @param string|array<mixed>|Collection<array-key, mixed> $container
     */
    public static function optionsForSelect(
        string|array|Collection|null $container,
        mixed $selected = null
    ): HtmlString {
        if (is_string($container)) {
            return new HtmlString($container);
        }

        list($selected, $disabled) = array_map(function ($r) {
            return array_map(function ($v) {
                if (is_bool($v)) {
                    return $v ? 'true' : 'false';
                }

                return '' . $v;
            }, (array)$r);
        }, static::extractSelectedAndDisabled($selected));

        $pieces = collect($container)->map(function ($element, $key) use ($selected, $disabled) {
            $htmlAttributes = static::optionHtmlAttributes($element);

            list($text, $value) = array_map(function ($v) {
                if (is_bool($v)) {
                    return $v ? 'true' : 'false';
                }

                return '' . $v;
            }, static::optionTextAndValue($element, $key));

            if (!Arr::get($htmlAttributes, 'selected')) {
                $htmlAttributes['selected'] = static::isOptionValueSelected($value, $selected);
            }
            if (!Arr::get($htmlAttributes, 'disabled')) {
                $htmlAttributes['disabled'] = $disabled && static::isOptionValueSelected($value, $disabled);
            }
            $htmlAttributes['value'] = $value;

            return static::newTagBuilder()->contentTagString('option', $text, $htmlAttributes);
        });

        return new HtmlString(implode("\n", $pieces->all()));
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder $collection
     */
    public static function optionsFromCollectionForSelect(
        array|Collection|QueryBuilder $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        mixed $selected = null
    ): HtmlString {
        if ($collection instanceof QueryBuilder) {
            $collection = $collection->get();
        }

        $options = collect($collection)->map(function ($element, $key) use ($textMethod, $valueMethod) {
            return [
                static::valueForCollection($element, $textMethod, $key),
                static::valueForCollection($element, $valueMethod, $key),
                static::optionHtmlAttributes($element)
            ];
        });

        list($selected, $disabled) = static::extractSelectedAndDisabled($selected);
        $selectDeselect = [
            'selected' => static::extractValuesFromCollection($collection, $valueMethod, $selected),
            'disabled' => static::extractValuesFromCollection($collection, $valueMethod, $disabled)
        ];

        return static::optionsForSelect($options, $selectDeselect);
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder $collection
     */
    public static function optionGroupsFromCollectionForSelect(
        array|Collection|QueryBuilder $collection,
        string|int|Closure $groupMethod,
        string|int|Closure $groupLabelMethod,
        string|int|Closure $optionKeyMethod,
        string|int|Closure $optionValueMethod,
        mixed $selectedKey = null
    ): HtmlString {
        $optGroupFn = function (
            $group,
            $groupKey
        ) use (
            $groupMethod,
            $groupLabelMethod,
            $optionKeyMethod,
            $optionValueMethod,
            $selectedKey
        ) {
            $optionTags = static::optionsFromCollectionForSelect(
                static::valueForCollection($group, $groupMethod, $groupKey),
                $optionKeyMethod,
                $optionValueMethod,
                $selectedKey
            );

            return static::contentTag(
                'optgroup',
                $optionTags,
                ['label' => static::valueForCollection($group, $groupLabelMethod, $groupKey)]
            );
        };

        $pieces = $collection->map($optGroupFn);

        return new HtmlString(implode('', $pieces->all()));
    }

    /**
     * @param string|Stringable|array<mixed>|Collection<array-key, mixed>|null $choices
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function select(
        string $object,
        string $method,
        string|Stringable|array|Collection|null $choices = null,
        array $options = [],
        array $htmlOptions = [],
        ?Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = static::yieldingSelect($object, $method, $choices, $options, $htmlOptions, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->choices = $block();
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param string|string[]|null $priorityZones
     * @param array<string>|Collection<int,string>|null $model
     */
    public static function timeZoneOptionsForSelect(
        mixed $selected = null,
        string|array|null $priorityZones = null,
        array|Collection|null $model = null
    ): HtmlString {
        $zoneOptions = '';

        $zones = collect($model ?: timezone_identifiers_list());

        if ($priorityZones) {
            if (is_string($priorityZones) && $priorityZones[0] === '/') {
                $priorityZones = $zones->filter(function ($z) use ($priorityZones) {
                    return preg_match($priorityZones, $z);
                });
            }

            $zoneOptions .= static::optionsForSelect($priorityZones, $selected);
            $zoneOptions .= static::contentTag('option', '-------------', ['value' => '', 'disabled' => true]);
            $zoneOptions .= "\n";

            $zones = $zones->diff($priorityZones);
        }

        $zoneOptions .= static::optionsForSelect($zones, $selected);

        return new HtmlString($zoneOptions);
    }

    /**
     * @param string|string[]|null $priorityZones
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function timeZoneSelect(
        string $object,
        string $method,
        string|array|null $priorityZones = null,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return (new Tags\TimeZoneSelect($object, $method, static::class, $priorityZones, $options, $htmlOptions))
            ->render();
    }

    public static function weekdayOptionsForSelect(
        mixed $selected = null,
        bool $indexAsValue = false,
        string $dayFormat = 'day_names',
        int $beginningOfWeek = 1
    ): HtmlString {
        $dayNames = trans('date.' . $dayFormat);
        if (is_string($dayNames)) {
            $dayNames = Carbon::getDays();
        }
        if ($indexAsValue) {
            $dayNames = array_flip($dayNames);
        }

        $rotateFn = function ($array, $distance = 1) {
            $distance %= count($array);
            return array_merge(array_splice($array, $distance), $array);
        };

        $dayNames = $rotateFn($dayNames, $beginningOfWeek);

        return static::optionsForSelect($dayNames, $selected);
    }

    /**
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public static function weekdaySelect(
        string $object,
        string $method,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return (new Tags\WeekdaySelect($object, $method, static::class, $options, $htmlOptions))->render();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     * @return HtmlStringGenerator
     */
    public static function yieldingCollectionCheckBoxes(
        string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        bool $yield = true
    ): Generator {
        $tag = new Tags\CollectionCheckBoxes(
            $object,
            $method,
            static::class,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions
        );

        $generator = $tag->yieldingRender($yield);
        if ($yield) {
            foreach ($generator as $obj) {
                yield $obj;
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     * @return HtmlStringGenerator
     */
    public static function yieldingCollectionRadioButtons(
        string $object,
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        bool $yield = false
    ): Generator {
        $tag = new Tags\CollectionRadioButtons(
            $object,
            $method,
            static::class,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions
        );

        $generator = $tag->yieldingRender($yield);
        if ($yield) {
            foreach ($generator as $obj) {
                yield $obj;
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param string|Stringable|array<mixed>|Collection<array-key, mixed>|null $choices
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     * @return HtmlStringGenerator
     */
    public static function yieldingSelect(
        string $object,
        string $method,
        string|Stringable|array|Collection|null $choices = null,
        array $options = [],
        array $htmlOptions = [],
        bool $yield = false
    ): Generator {
        if ($yield) {
            $obj = (object)[
                'choices' => '',
            ];
            yield $obj;
            $choices = $obj->choices;
        }

        return (new Tags\Select($object, $method, static::class, $choices, $options, $htmlOptions, null))->render();
    }

    /**
     * @return OptionHash
     */
    protected static function optionHtmlAttributes(mixed $element): array {
        if (is_array($element)) {
            $hashes = array_filter($element, function ($e) {
                return is_array($e) && Arr::isAssoc($e);
            });

            return array_reduce($hashes, function ($memo, $h) {
                return array_merge($memo, $h);
            }, []);
        }

        return [];
    }

    /**
     * @return array{0:mixed,1:mixed}
     */
    protected static function optionTextAndValue(mixed $option, mixed $key): array {
        if (is_array($option)) {
            $option = array_filter($option, function ($e) {
                return !is_array($e);
            });

            return count($option) > 1
                ? [$option[0], $option[1]]
                : [$option[0], $option[0]];
        } elseif (is_string($key)) {
            return [$key, $option];
        }

        return [$option, $option];
    }

    protected static function isOptionValueSelected(mixed $value, mixed $selected): bool {
        return in_array($value, (array)$selected);
    }

    /**
     * @return array{0:mixed,1:mixed}
     */
    protected static function extractSelectedAndDisabled(mixed $selected): array {
        if (is_callable($selected) || is_string($selected)) {
            return [$selected, null];
        } else {
            $selected = (array)$selected;
            if (Arr::isAssoc($selected)) {
                $options = $selected;
            } else {
                $options = [
                    'selected' => $selected
                ];
            }

            $selectedItems = Arr::get($options, 'selected');
            return [$selectedItems, Arr::get($options, 'disabled')];
        }
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed> $collection
     */
    protected static function extractValuesFromCollection(
        array|Collection $collection,
        string|int|Closure $valueMethod,
        mixed $selected
    ): mixed {
        if (is_callable($selected)) {
            return $collection->map(function ($element) use ($selected, $valueMethod) {
                if ($selected($element)) {
                    return $element->{$valueMethod};
                }
            })->filter()->all();
        }

        return $selected;
    }

    protected static function valueForCollection(
        mixed $item,
        string|int|Closure $value,
        string|int $key = null
    ): mixed {
        return ($value instanceof Closure)
            ? $value($item, $key)
            : (is_array($item) && is_numeric($value) ? $item[$value] : $item->{$value});
    }

    protected static function promptText(mixed $prompt): string {
        return is_string($prompt)
            ? $prompt
            : StrUtils::translate(['helpers.select.prompt'], 'Please select');
    }
}
