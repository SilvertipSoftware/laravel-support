<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Generator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\ForwardsCalls;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\Builder as CollectionBuilder;
use SilvertipSoftware\LaravelSupport\Blade\Tags\Label\LabelBuilder;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;
use SilvertipSoftware\LaravelSupport\Routing\RestRouter;
use Stringable;

/**
 * @phpstan-type HtmlStringGenerator Generator<int,\stdClass,null,HtmlString>
 * @phpstan-type OptionHash array<string,mixed>
 */
trait FormHelper {
    use FormTagHelper,
        ModelUtils,
        TagHelper;

    /** @var class-string<FormBuilder> */
    public static string $defaultFormBuilderClass = FormBuilder::class;
    public static bool $formWithGeneratesIds = true;
    public static bool $formWithGeneratesRemoteForms = true;
    public static bool $multipleFileFieldIncludeHidden = false;

    /** @var array<FormBuilder|LabelBuilder|CollectionBuilder> */
    protected static array $builders = [];

    /**
     * @param OptionHash $options
     */
    public static function fields(
        ?string $scope = null,
        ?object $model = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        if (!is_callable($block)) {
            throw new RuntimeException('fields requires a callback');
        }

        $generator = static::yieldingFields($scope, $model, $options);
        foreach ($generator as $genObj) {
            $genObj->content = $block($genObj->builder);
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public static function fieldsFor(
        string|object $recordName,
        ?object $recordObject = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $generator = static::yieldingFieldsFor($recordName, $recordObject, $options);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder);
        }

        return $generator->getReturn();
    }

    /**
     * @param array<string|Model|FluentModel> $model
     * @param OptionHash $options
     */
    public static function formWith(
        array|object|null $model = null,
        ?string $scope = null,
        string|Model|FluentModel|null $url = null,
        ?string $format = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = static::yieldingFormWith($model, $scope, $url, $format, $options, $yield);

        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public static function checkBox(
        string $objectName,
        string $method,
        array $options = [],
        string|int|bool $checkedValue = "1",
        string|int|bool|null $uncheckedValue = "0"
    ): HtmlString {
        return (new Tags\CheckBox($objectName, $method, static::class, $checkedValue, $uncheckedValue, $options))
            ->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function colorField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\ColorField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function dateField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\DateField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function datetimeField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\DatetimeLocalField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function datetimeLocalField(string $objectName, string $method, array $options = []): HtmlString {
        return static::datetimeField($objectName, $method, $options);
    }

    /**
     * @param OptionHash $options
     */
    public static function emailField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\EmailField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function fileField(string $objectName, string $method, array $options = []): HtmlString {
        $options = array_merge(
            ['include_hidden' => static::$multipleFileFieldIncludeHidden],
            $options
        );

        $options = static::convertDirectUploadOptionToUrl($options);

        return (new Tags\FileField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function hiddenField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\HiddenField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param string|Stringable|OptionHash $contentOrOptions
     * @param OptionHash $options
     */
    public static function label(
        string $objectName,
        string $method,
        string|Stringable|array $contentOrOptions = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = static::yieldingLabel($objectName, $method, $contentOrOptions, $options, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public static function monthField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\MonthField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function numberField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\NumberField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function passwordField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\PasswordField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function phoneField(string $objectName, string $method, array $options = []): HtmlString {
        return static::telephoneField($objectName, $method, $options);
    }

    /**
     * @param OptionHash $options
     */
    public static function radioButton(
        string $objectName,
        string $method,
        mixed $tagValue,
        array $options = []
    ): HtmlString {
        return (new Tags\RadioButton($objectName, $method, static::class, $tagValue, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function rangeField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\RangeField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function searchField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\SearchField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function telephoneField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\TelField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function textArea(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\TextArea($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function textField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\TextField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function timeField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\TimeField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function urlField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\UrlField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param OptionHash $options
     */
    public static function weekField(string $objectName, string $method, array $options = []): HtmlString {
        return (new Tags\WeekField($objectName, $method, static::class, $options))->render();
    }

    /**
     * @param array<object>|object $object
     */
    public static function objectForFormBuilder(array|object $object): object {
        return is_array($object)
            ? $object[count($object) - 1]
            : $object;
    }

    /**
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public static function yieldingFields(
        string|object|null $scope,
        ?object $model = null,
        array $options = []
    ): Generator {
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

        // @phpstan-ignore-next-line
        return $obj->content instanceof HtmlString
            ? $obj->content
            : new HtmlString($obj->content);
    }

    /**
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public static function yieldingFieldsFor(
        string|Model|FluentModel $recordName,
        ?object $recordObject = null,
        array $options = []
    ): Generator {
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

    /**
     * @param array<string|object>|object|null $model
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public static function yieldingFormWith(
        array|object|null $model = null,
        ?string $scope = null,
        string|object|null $url = null,
        ?string $format = null,
        array $options = [],
        bool $yield = true
    ): Generator {
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

    /**
     * @param string|OptionHash|null $contentOrOptions
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public static function yieldingLabel(
        string $objectName,
        string $method,
        string|array|null $contentOrOptions = null,
        array $options = [],
        bool $yield = true
    ): Generator {
        $tag = new Tags\Label($objectName, $method, static::class, $contentOrOptions, $options);
        $generator = $tag->yieldingRender($yield);
        if ($yield) {
            foreach ($generator as $obj) {
                yield $obj;
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param string|OptionHash $urlForOptions
     * @param OptionHash $options
     * @return OptionHash
     */
    protected static function htmlOptionsForFormWith(
        string|object|array|null $urlForOptions = null,
        ?object $model = null,
        array $options = []
    ): array {
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

    /**
     * @param OptionHash $options
     */
    protected static function instantiateBuilder(
        string|object|null $modelName,
        ?object $modelObject,
        array $options
    ): FormBuilder {
        if (is_string($modelName)) {
            $object = $modelObject;
            $objectName = $modelName;
        } else {
            $object = $modelName;
            $objectName = $object ? static::modelNameFrom($object)->param_key : '';
        }

        $builderClass = Arr::get($options, 'builder') ?? static::$defaultFormBuilderClass;
        return new $builderClass($objectName, $object, static::class, $options);
    }

    protected static function objectExists(?object $object): bool {
        return is_object($object) && (property_exists($object, 'exists') || method_exists($object, '__get'))
            ? $object->exists
            : false;
    }

    protected static function pushBuilder(FormBuilder|LabelBuilder|CollectionBuilder $builder): void {
        static::$builders[] = $builder;
    }

    protected static function popBuilder(): FormBuilder|LabelBuilder|CollectionBuilder {
        return array_pop(static::$builders);
    }
}
