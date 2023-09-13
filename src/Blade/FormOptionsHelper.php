<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

trait FormOptionsHelper {
    use TagHelper;

    public static function collectionCheckBoxes(
        $object,
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = [],
        $block = null
    ) {
        return (new Tags\CollectionCheckBoxes(
            $object,
            $method,
            static::class,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions
        ))->render($block);
    }

    public static function collectionRadioButtons(
        $object,
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = [],
        $block = null
    ) {
        return (new Tags\CollectionRadioButtons(
            $object,
            $method,
            static::class,
            $collection,
            $valueMethod,
            $textMethod,
            $options,
            $htmlOptions
        ))->render($block);
    }

    public static function collectionSelect(
        $object,
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = []
    ) {
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

    public static function groupedCollectionSelect(
        $object,
        $method,
        $collection,
        $groupMethod,
        $groupLabelMethod,
        $optionKeyMethod,
        $optionValueMethod,
        $options = [],
        $htmlOptions = []
    ) {
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

    public static function groupedOptionsForSelect($groupedOptions, $selectedKey = null, $options = []) {
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

    public static function optionsForSelect($container, $selected = null) {
        if (is_string($container)) {
            return $container;
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

    public static function optionsFromCollectionForSelect($collection, $valueMethod, $textMethod, $selected = null) {
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

    public static function optionGroupsFromCollectionForSelect(
        $collection,
        $groupMethod,
        $groupLabelMethod,
        $optionKeyMethod,
        $optionValueMethod,
        $selectedKey = null
    ) {
        $optGroupFn = function ($group, $groupKey) use (
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

    public static function select($object, $method, $choices = null, $options = [], $htmlOptions = [], $block = null) {
        return (new Tags\Select($object, $method, static::class, $choices, $options, $htmlOptions, $block))->render();
    }

    public static function timeZoneOptionsForSelect($selected = null, $priorityZones = null, $model = null) {
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

    public static function timeZoneSelect($object, $method, $priorityZones = null, $options = [], $htmlOptions = []) {
        return (new Tags\TimeZoneSelect($object, $method, static::class, $priorityZones, $options, $htmlOptions))
            ->render();
    }

    public static function weekdayOptionsForSelect($selected = null, $indexAsValue = false, $dayFormat = 'day_names', $beginningOfWeek = 1) {
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

    public static function weekdaySelect($object, $method, $options = [], $htmlOptions = []) {
        return (new Tags\WeekdaySelect($object, $method, static::class, $options, $htmlOptions))->render();
    }

    protected static function optionHtmlAttributes($element) {
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

    protected static function optionTextAndValue($option, $key) {
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

    protected static function isOptionValueSelected($value, $selected) {
        return in_array($value, (array)$selected);
    }

    protected static function extractSelectedAndDisabled($selected) {
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

    protected static function extractValuesFromCollection($collection, $valueMethod, $selected) {
        if (is_callable($selected)) {
            return $collection->map(function($element) use ($selected, $valueMethod) {
                if ($selected($element)) {
                    return $element->{$valueMethod};
                }
            })->filter()->all();
        }

        return $selected;
    }

    protected static function valueForCollection($item, $value, $key = null) {
        return ($value instanceof Closure)
            ? $value($item, $key)
            : (is_array($item) && is_numeric($value) ? $item[$value] : $item->{$value});
    }

    protected static function promptText($prompt) {
        return is_string($prompt)
            ? $prompt
            : StrUtils::translate(['helpers.select.prompt'], 'Please select');
    }
}
