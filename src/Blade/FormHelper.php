<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;

trait FormHelper {
    use FormTagHelper,
        ModelUtils,
        TagHelper;

    public static $defaultFormBuilderClass = FormBuilder::class;
    public static $formWithGeneratesIds = true;
    public static $formWithGeneratesRemoteForms = true;
    public static $multipleFileFieldIncludeHidden = false;

    protected static $builders = [];

    public static function fields($scope = null, $model = null, $options = [], $block = null) {
        if (!is_callable($block)) {
            throw new RuntimeException('fields requires a callback');
        }

        $generator = static::yieldingFields($scope, $model, $options);
        foreach ($generator as $genObj) {
            $genObj->content = $block($genObj->builder);
        }

        return $generator->getReturn();
    }

    public static function fieldsFor($recordName, $recordObject = null, $options = [], $block = null) {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $generator = static::yieldingFieldsFor($recordName, $recordObject, $options);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder);
        }

        return $generator->getReturn();
    }

    public static function formWith(
        $model = null,
        $scope = null,
        $url = null,
        $format = null,
        $options = [],
        $block = null
    ) {
        $yield = $block != null;
        $generator = static::yieldingFormWith($model, $scope, $url, $format, $options, $yield);

        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    public static function checkBox($objectName, $method, $options = [], $checkedValue = "1", $uncheckedValue = "0") {
        return (new Tags\CheckBox($objectName, $method, static::class, $checkedValue, $uncheckedValue, $options))
            ->render();
    }

    public static function colorField($objectName, $method, $options = []) {
        return (new Tags\ColorField($objectName, $method, static::class, $options))->render();
    }

    public static function dateField($objectName, $method, $options = []) {
        return (new Tags\DateField($objectName, $method, static::class, $options))->render();
    }

    public static function datetimeField($objectName, $method, $options = []) {
        return (new Tags\DatetimeLocalField($objectName, $method, static::class, $options))->render();
    }

    public static function datetimeLocalField($objectName, $method, $options = []) {
        return static::datetimeField($objectName, $method, $options);
    }

    public static function emailField($objectName, $method, $options = []) {
        return (new Tags\EmailField($objectName, $method, static::class, $options))->render();
    }

    public static function fileField($objectName, $method, $options = []) {
        $options = array_merge(
            ['include_hidden' => static::$multipleFileFieldIncludeHidden],
            $options
        );

        $options = static::convertDirectUploadOptionToUrl($options);

        return (new Tags\FileField($objectName, $method, static::class, $options))->render();
    }

    public static function hiddenField($objectName, $method, $options = []) {
        return (new Tags\HiddenField($objectName, $method, static::class, $options))->render();
    }

    public static function label($objectName, $method, $contentOrOptions = null, $options = [], $block = null) {
        $yield = $block != null;
        $generator = static::yieldingLabel($objectName, $method, $contentOrOptions, $options, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    public static function monthField($objectName, $method, $options = []) {
        return (new Tags\MonthField($objectName, $method, static::class, $options))->render();
    }

    public static function numberField($objectName, $method, $options = []) {
        return (new Tags\NumberField($objectName, $method, static::class, $options))->render();
    }

    public static function passwordField($objectName, $method, $options = []) {
        return (new Tags\PasswordField($objectName, $method, static::class, $options))->render();
    }

    public static function phoneField($objectName, $method, $options = []) {
        return static::telephoneField($objectName, $method, $options);
    }

    public static function radioButton($objectName, $method, $tagValue, $options = []) {
        return (new Tags\RadioButton($objectName, $method, static::class, $tagValue, $options))->render();
    }

    public static function rangeField($objectName, $method, $options = []) {
        return (new Tags\RangeField($objectName, $method, static::class, $options))->render();
    }

    public static function searchField($objectName, $method, $options = []) {
        return (new Tags\SearchField($objectName, $method, static::class, $options))->render();
    }

    public static function telephoneField($objectName, $method, $options = []) {
        return (new Tags\TelField($objectName, $method, static::class, $options))->render();
    }

    public static function textArea($objectName, $method, $options = []) {
        return (new Tags\TextArea($objectName, $method, static::class, $options))->render();
    }

    public static function textField($objectName, $method, $options = []) {
        return (new Tags\TextField($objectName, $method, static::class, $options))->render();
    }

    public static function timeField($objectName, $method, $options = []) {
        return (new Tags\TimeField($objectName, $method, static::class, $options))->render();
    }

    public static function urlField($objectName, $method, $options = []) {
        return (new Tags\UrlField($objectName, $method, static::class, $options))->render();
    }

    public static function weekField($objectName, $method, $options = []) {
        return (new Tags\WeekField($objectName, $method, static::class, $options))->render();
    }

    public static function objectForFormBuilder($object) {
        return is_array($object)
            ? $object[count($object) - 1]
            : $object;
    }

    public static function yieldingFields($scope, $model = null, $options = []) {
        $defaultOpts = [
            'allow_method_names_outside_object' => true,
            'skip_default_ids' => !static::$formWithGeneratesIds
        ];
        $options = array_merge($defaultOpts, $options);

        if ($model) {
            $model = static::objectForFormBuilder($model);
            $scope = $scope ?? static::modelNameFrom($model)->param_key;
        }

        $builder = static::instantiateBuilder($scope, $model, $options);
        static::pushBuilder($builder);
        $obj = (object)[
            'builder' => $builder,
            'content' => ''
        ];
        yield $obj;
        static::popBuilder();

        return $obj->content;
    }

    public static function yieldingFieldsFor($recordName, $recordObject = null, $options = []) {
        $defaultOpts = [
            'allow_method_names_outside_object' => false,
            'skip_default_ids' => false
        ];
        $options = array_merge($defaultOpts, $options);

        $generator = static::yieldingFields($recordName, $recordObject, $options);
        foreach ($generator as $obj) {
            yield $obj;
        }

        return $generator->getReturn();
    }

    public static function yieldingFormWith(
        $model = null,
        $scope = null,
        $url = null,
        $format = null,
        $options = [],
        $yield = true
    ) {
        $defaultOpts = [
            'allow_method_names_outside_object' => true,
            'skip_default_ids' => !static::$formWithGeneratesIds
        ];
        $options = array_merge($defaultOpts, $options);

        if ($model) {
            if ($url !== false) {
                $url = $url ?? RestRouter::path($model, ['format' => $format]);
            }

            $model = static::objectForFormBuilder($model);
            $scope = $scope ?? static::modelNameFrom($model)->param_key;
        }

        $builder = static::instantiateBuilder($scope, $model, $options);
        static::pushBuilder($builder);

        if ($yield) {
            $obj = (object)[
                'builder' => $builder,
                'content' => ''
            ];
            yield $obj;

            $options['multipart'] = Arr::get($options, 'multipart', $builder->isMultipart);
            static::popBuilder();

            $htmlOptions = static::htmlOptionsForFormWith($url, $model, $options);
            return static::formTagWithBody($htmlOptions, $obj->content);
        } else {
            $htmlOptions = static::htmlOptionsForFormWith($url, $model, $options);
            return static::formTagHtml($htmlOptions);
        }
    }

    public static function yieldingLabel(
        $objectName,
        $method,
        $contentOrOptions = null,
        $options = [],
        $yield = true
    ) {
        $tag = new Tags\Label($objectName, $method, static::class, $contentOrOptions, $options);
        $generator = $tag->yieldingRender($yield);
        if ($yield) {
            foreach ($generator as $obj) {
                yield $obj;
            }
        }

        return $generator->getReturn();
    }

    protected static function htmlOptionsForFormWith($urlForOptions = null, $model = null, $options = []) {
        $html = Arr::pull($options, 'html', []);
        $local = Arr::pull($options, 'local', !static::$formWithGeneratesRemoteForms);
        $skipEnforcingUtf8 = Arr::pull($options, 'skip_enforcing_utf8');

        $htmlOptions = array_merge(
            Arr::only($options, ['id', 'class', 'multipart', 'method', 'data', 'csrf_token']),
            $html
        );
        $htmlOptions['remote'] = Arr::pull($html, 'remote') ?? !$local;

        if (static::objectExists($model)) {
            $htmlOptions['method'] = $htmlOptions['method'] ?? 'patch';
        }

        if ($skipEnforcingUtf8 === null) {
            if (Arr::exists($options, 'enforce_utf8')) {
                $htmlOptions['enforce_utf8'] = Arr::get($options, 'enforce_utf8');
            }
        } else {
            $htmlOptions['enforce_utf8'] = !$skipEnforcingUtf8;
        }

        return static::htmlOptionsForForm($urlForOptions ?? [], $htmlOptions);
    }

    protected static function instantiateBuilder($modelName, $modelObject, $options) {
        if (is_string($modelName)) {
            $object = $modelObject;
            $objectName = $modelName;
        } else {
            $object = $modelName;
            $objectName = $object ? static::modelNameFrom($object)->param_key : null;
        }

        $builderClass = Arr::get($options, 'builder') ?? static::$defaultFormBuilderClass;
        return new $builderClass($objectName, $object, static::class, $options);
    }

    protected static function objectExists($object) {
        return is_object($object) && (property_exists($object, 'exists') || method_exists($object, '__get'))
            ? $object->exists
            : false;
    }

    protected static function pushBuilder($builder) {
        static::$builders[] = $builder;
    }

    protected static function popBuilder() {
        return array_pop(static::$builders);
    }
}
