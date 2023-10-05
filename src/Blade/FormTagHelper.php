<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Generator;
use Stringable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * @phpstan-type OptionHash array<string,mixed>
 */
trait FormTagHelper {
    use TagHelper,
        UrlHelper;

    public static bool $automaticallyDisableSubmitTag = true;
    public static bool $embedCsrfInRemoteForms = true;
    public static bool $protectAgainstForgery = true;
    public static bool $defaultEnforceUtf8 = true;

    /**
     * @param OptionHash $options
     */
    public static function buttonTag(
        string|Stringable|null $content = null,
        array $options = [],
        Closure $block = null
    ): HtmlString {
//        list($content, $options, $block) = Utils::determineTagArgs($content, $options, $block);

        $yield = $block != null;
        $generator = static::yieldingButtonTag($content, $options, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block();
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public static function checkBoxTag(
        string $name,
        mixed $value = "1",
        bool $checked = false,
        array $options = []
    ): HtmlString {
        $opts = [
            'type' => 'checkbox',
            'name' => $name,
            'id' => static::sanitizeToId($name),
            'value' => $value
        ];

        $htmlOptions = array_merge($opts, $options);
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        }

        return static::tag('input', $htmlOptions);
    }

    /**
     * @param OptionHash $options
     */
    public static function colorFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'color']));
    }

    /**
     * @param OptionHash $options
     */
    public static function dateFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'date']));
    }

    /**
     * @param OptionHash $options
     */
    public static function datetimeFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'datetime-local']));
    }

    /**
     * @param OptionHash $options
     */
    public static function emailFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'email']));
    }

    /**
     * @param OptionHash $options
     */
    public static function fileFieldTag(string $name, array $options = []): HtmlString {
        $opts = array_merge($options, ['type' => 'file']);

        return static::textFieldTag($name, null, static::convertDirectUploadOptionToUrl($opts));
    }

    /**
     * @param OptionHash $options
     */
    public static function hiddenFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        $opts = array_merge($options, ['type' => 'hidden', 'autocomplete' => 'off']);

        return static::textFieldTag($name, $value, $opts);
    }

    /**
     * @param string[] $suffixes
     */
    public static function fieldId(
        object|string $objectOrName,
        string $methodName,
        array $suffixes = [],
        string|int|null $index = null,
        ?string $namespace = null
    ): string {
        if (is_object($objectOrName) && method_exists($objectOrName, 'modelName')) {
            $objectOrName = $objectOrName->modelName()->singular;
        }

        $sanitizedObjectName = static::deleteSuffix(preg_replace('/\]\[|[^-a-zA-Z0-9:.]/', '_', $objectOrName), '_');
        $sanitizedMethodName = static::deleteSuffix($methodName, '\?');

        $arr = array_merge(
            [
                $namespace,
                $sanitizedObjectName ?: null,
                empty($sanitizedObjectName) ? null : $index,
                $sanitizedMethodName
            ],
            $suffixes
        );

        return implode('_', array_filter($arr, function ($v) {
            return $v !== null;
        }));
    }

    /**
     * @param string|string[] $otherNames
     */
    public static function fieldName(
        object|string|null $objectOrName,
        string $methodName,
        string|array $otherNames = [],
        bool $multiple = false,
        string|int|null $index = null
    ): string {
        $names = implode(
            '',
            array_map(function ($name) {
                return '[' . $name . ']';
            }, $otherNames)
        );

        if (empty($objectOrName)) {
            return $methodName . $names . ($multiple ? '[]' : '');
        } elseif ($index !== null) {
            return $objectOrName . '[' . $index . '][' . $methodName . ']' . $names . ($multiple ? '[]' : '');
        } else {
            return $objectOrName . '[' . $methodName . ']' . $names . ($multiple ? '[]' : '');
        }
    }

    /**
     * @param OptionHash $options
     */
    public static function formTagHtml(array $options): HtmlString {
        $extraTags = static::extraTagsForForm($options);

        return new HtmlString(static::tag('form', $options, true) . $extraTags);
    }

    /**
     * @param OptionHash $options
     */
    public static function formTagWithBody(array $options, string|Stringable|null $content = null): HtmlString {
        return new HtmlString(static::formTagHtml($options) . $content . '</form>');
    }

    /**
     * @param string|Stringable|OptionHash|Closure $contentOrOptions
     * @param OptionHash $options
     */
    public static function labelTag(
        string $name = null,
        string|Stringable|array|Closure $contentOrOptions = null,
        array|Closure $options = [],
        ?Closure $block = null
    ): HtmlString {
        list($content, $options, $block) = Utils::determineTagArgs($contentOrOptions, $options, $block);

        $yield = $block != null;
        $generator = static::yieldingLabelTag($name, $content, $options ?? [], $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block();
            }
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public static function monthFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'month']));
    }

    /**
     * @param OptionHash $options
     */
    public static function numberFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        $type = Arr::pull($options, 'type', 'number');
        $range = Arr::pull($options, 'range', null);
        if ($range && is_array($range) && count($range) == 2) {
            $options = array_merge($options, ['min' => $range[0], 'max' => $range[1]]);
        }

        return static::textFieldTag($name, $value, array_merge($options, ['type' => $type]));
    }

    /**
     * @param OptionHash $options
     */
    public static function passwordFieldTag(
        string $name = 'password',
        mixed $value = null,
        array $options = []
    ): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'password']));
    }

    /**
     * @param OptionHash $options
     */
    public static function radioButtonTag(
        string $name,
        mixed $value,
        mixed $checked = false,
        array $options = []
    ): HtmlString {
        $opts = [
            'type' => 'radio',
            'name' => $name,
            'id' => static::sanitizeToId($name) . '_' . static::sanitizeToId($value),
            'value' => $value
        ];

        $htmlOptions = array_merge($opts, $options);
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        }

        return static::tag('input', $htmlOptions);
    }

    /**
     * @param OptionHash $options
     */
    public static function rangeFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::numberFieldTag($name, $value, array_merge($options, ['type' => 'range']));
    }

    /**
     * @param OptionHash $options
     */
    public static function searchFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'search']));
    }

    /**
     * @param OptionHash $options
     */
    public static function submitTag(string $value = "Save changes", array $options = []): HtmlString {
        $opts = [
            'type' => 'submit',
            'name' => 'commit',
            'value' => $value
        ];

        $htmlOptions = array_merge($opts, $options);
        static::setDefaultDisableWith($value, $htmlOptions);
        return static::tag('input', $htmlOptions);
    }

    /**
     * @param OptionHash $options
     */
    public static function textAreaTag(
        string $name,
        string|Stringable|null $content = null,
        array $options = []
    ): HtmlString {
        $size = Arr::pull($options, 'size');
        if (is_string($size)) {
            list($options['cols'], $options['rows']) = explode('x', $size);
        }

        $escape = Arr::pull($options, 'escape', true);
        $content = $escape ? e($content) : $content;

        $opts = [
            'name' => $name,
            'id' => static::sanitizeToId($name)
        ];

        return static::contentTag('textarea', new HtmlString($content), array_merge($opts, $options));
    }

    /**
     * @param OptionHash $options
     */
    public static function textFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        $opts = [
            'type' => 'text',
            'name' => $name,
            'id' => static::sanitizeToId($name),
            'value' => $value
        ];

        return static::tag('input', array_merge($opts, $options));
    }

    /**
     * @param OptionHash $options
     */
    public static function timeFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'time']));
    }

    /**
     * @param OptionHash $options
     */
    public static function urlFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'url']));
    }

    /**
     * @param OptionHash $options
     */
    public static function weekFieldTag(string $name, mixed $value = null, array $options = []): HtmlString {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'week']));
    }

    /**
     * @param OptionHash $options
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public static function yieldingButtonTag(
        string|Stringable|null $content = null,
        array $options = [],
        bool $yield = true
    ): Generator {
        list($content, $options) = Utils::determineTagArgs($content, $options);
        $options = $options ?? [];

        $opts = [
            'name' => 'button',
            'type' => 'submit'
        ];
        $options = array_merge($opts, $options);

        $generator = static::yieldingContentTag('button', $content ?? 'Button', $options, !$yield, $yield);
        yield from $generator;
        return $generator->getReturn();
    }

    /**
     * @param string|Stringable|OptionHash|null $contentOrOptions
     * @param OptionHash $options
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public static function yieldingLabelTag(
        ?string $name = null,
        string|Stringable|array|null $contentOrOptions = null,
        array $options = [],
        bool $yield = true
    ): Generator {
        list($content, $options) = Utils::determineTagArgs($contentOrOptions, $options);
        $options = $options ?? [];

        if (!empty($name) && !array_key_exists('for', $options)) {
            $options['for'] = static::sanitizeToId($name);
        }

        $generator = static::yieldingContentTag(
            'label',
            $content ?? Str::title(Str::slug($name)),
            $options,
            $yield,
            $yield
        );
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     * @return OptionHash
     */
    protected static function convertDirectUploadOptionToUrl(array $options): array {
        if (Arr::pull($options, 'direct_upload') && method_exists(static::class, 'directUploadsUrl')) {
            $options['data-direct-upload-url'] = static::directUploadsUrl();
        }

        return $options;
    }

    protected static function deleteSuffix(string $str, string $suffix): string {
        return preg_replace('/' . $suffix . '$/', '', $str);
    }

    protected static function directUploadsUrl(): ?string {
        return null;
    }

    /**
     * @param OptionHash $options
     */
    protected static function extraTagsForForm(&$options): HtmlString {
        $csrf_token = Arr::pull($options, 'csrf_token');
        $method = strtolower(Arr::pull($options, 'method', 'post'));

        $tags = '';
        switch ($method) {
            case "get":
                $options['method'] = 'get';
                break;
            case "post":
                $options['method'] = 'post';
                $tags .= static::tokenTag($csrf_token, [
                    'action' => Arr::get($options, 'action'),
                    'method' => 'post'
                ]);
                break;
            default:
                $options['method'] = 'post';
                $tags .= static::methodTag($method) . static::tokenTag($csrf_token, [
                    'action' => Arr::get($options, 'action'),
                    'method' => $method
                ]);
                break;
        }

        if (Arr::pull($options, 'enforce_utf8', static::$defaultEnforceUtf8)) {
            $tags = static::utf8EnforcerTag() . $tags;
        }

        return new HtmlString($tags);
    }

    /**
     * @param string|bool|OptionHash $urlForOptions
     * @param OptionHash $options
     * @return OptionHash
     */
    protected static function htmlOptionsForForm(string|bool|object|array $urlForOptions, array $options): array {
        if (Arr::pull($options, 'multipart')) {
            $options['enctype'] = "multipart/form-data";
        }

        if ($urlForOptions === false || Arr::get($options, 'action') === false) {
            Arr::forget($options, 'action');
        } else {
            $options['action'] = static::urlFor($urlForOptions);
        }

        $options['accept-charset'] = 'UTF-8';

        if (Arr::pull($options, 'remote')) {
            $options['data-remote'] = true;
        }

        if (Arr::get($options, 'remote') && !static::$embedCsrfInRemoteForms && !Arr::get($options, 'csrf_token')) {
            $options['csrf_token'] = false;
        } elseif (Arr::get($options, 'csrf_token') === true) {
            $options['csrf_token'] = null;
        }

        return $options;
    }

    protected static function sanitizeToId(string|int|float|Stringable|null $name): string {
        return preg_replace('/[^-a-zA-Z0-9:\.]/', '_', str_replace(']', '', '' . $name));
    }

    protected static function methodTag(?string $method): HtmlString {
        return static::tag('input', [
            'type' => 'hidden',
            'name' => '_method',
            'value' => $method ?? 'post',
            'autocomplete' => 'off'
        ], false, false);
    }

    /**
     * @param OptionHash $options
     */
    protected static function tokenTag(?string $token, array $options): HtmlString {
        if (!static::$protectAgainstForgery) {
            return new HtmlString();
        }

        return static::tag('input', [
            'type' => 'hidden',
            'name' => '_token',
            'value' => $token ?? csrf_token(),
            'autocomplete' => 'off'
        ], false, false);
    }

    protected static function utf8EnforcerTag(): HtmlString {
        return new HtmlString('<input type="hidden" name="utf8" value="&#x2713;" autocomplete="off" />');
    }

    /**
     * @param OptionHash $options
     */
    protected static function setDefaultDisableWith(mixed $value, array &$options): void {
        $data = Arr::get($options, 'data', []);

        if (Arr::get($options, 'data-disable-with') === false || Arr::get($data, 'disable-with') === false) {
            unset($data['disable-with']);
        } elseif (static::$automaticallyDisableSubmitTag) {
            $data['disable-with'] = Arr::get(
                $options,
                'data-disable-with',
                Arr::get($data, 'disable-with', $value)
            );
        }

        unset($options['data-disable-with']);
        $options['data'] = $data;
    }
}
