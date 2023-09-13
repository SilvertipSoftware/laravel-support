<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait FormTagHelper {
    use TagHelper,
        UrlHelper;

    public static $automaticallyDisableSubmitTag = true;
    public static $embedCsrfInRemoteForms = true;
    public static $protectAgainstForgery = true;
    public static $defaultEnforceUtf8 = true;

    public static function buttonTag($content = null, $options = [], $block = null) {
        list($content, $options, $block) = Utils::determineTagArgs($content, $options, $block);
        $options = $options ?? [];

        $opts = [
            'name' => 'button',
            'type' => 'submit'
        ];
        $options = array_merge($opts, $options);

        if (is_callable($block)) {
            return static::contentTag('button', $options, $block);
        }

        return static::contentTag('button', $content ?? 'Button', $options);
    }

    public static function checkBoxTag($name, $value = "1", $checked = false, $options = []) {
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

    public static function colorFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'color']));
    }

    public static function dateFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'date']));
    }

    public static function datetimeFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'datetime-local']));
    }

    public static function emailFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'email']));
    }

    public static function hiddenFieldTag($name, $value = null, $options = []) {
        $opts = array_merge($options, ['type' => 'hidden', 'autocomplete' => 'off']);

        return static::textFieldTag($name, $value, $opts);
    }

    public static function fieldId($objectOrName, $methodName, array $suffixes = [], $index = null, $namespace = null) {
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

    public static function fieldName(
        $objectOrName,
        $methodName,
        array $otherNames = [],
        $multiple = false,
        $index = null
    ) {
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

    public static function formTag($urlForOptions = [], $options = []) {
        $htmlOptions = static::htmlOptionsForForm($urlForOptions, $options);

        return static::formTagHtml($htmlOptions);
    }

    public static function formTagHtml($options) {
        $extraTags = static::extraTagsForForm($options);

        return new HtmlString(static::tag('form', $options, true) . $extraTags);
    }

    public static function formTagWithBody($options, $content = null) {
        return new HtmlString(static::formTagHtml($options) . $content . '</form>');
    }

    public static function labelTag($name = null, $contentOrOptions = null, $options = [], $block = null) {
        list($content, $options, $block) = Utils::determineTagArgs($contentOrOptions, $options, $block);
        $options = $options ?? [];

        if (!empty($name) && !array_key_exists('for', $options)) {
            $options['for'] = static::sanitizeToId($name);
        }

        return static::contentTag('label', $content ?? Str::title(Str::slug($name)), $options, $block);
    }

    public static function monthFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'month']));
    }

    public static function numberFieldTag($name, $value = null, $options = []) {
        $type = Arr::pull($options, 'type', 'number');
        $range = Arr::pull($options, 'range', null);
        if ($range && is_array($range) && count($range) == 2) {
            $options = array_merge($options, ['min' => $range[0], 'max' => $range[1]]);
        }

        return static::textFieldTag($name, $value, array_merge($options, ['type' => $type]));
    }

    public static function passwordFieldTag($name = 'password', $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'password']));
    }

    public static function radioButtonTag($name, $value, $checked = false, $options = []) {
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

    public static function rangeFieldTag($name, $value = null, $options = []) {
        return static::numberFieldTag($name, $value, array_merge($options, ['type' => 'range']));
    }

    public static function searchFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'search']));
    }

    public static function submitTag($value = "Save changes", $options = []) {
        $opts = [
            'type' => 'submit',
            'name' => 'commit',
            'value' => $value
        ];

        $htmlOptions = array_merge($opts, $options);
        static::setDefaultDisableWith($value, $htmlOptions);
        return static::tag('input', $htmlOptions);
    }

    public static function textAreaTag($name, $content = null, $options = []) {
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

    public static function textFieldTag($name, $value = null, $options = []) {
        $opts = [
            'type' => 'text',
            'name' => $name,
            'id' => static::sanitizeToId($name),
            'value' => $value
        ];

        return static::tag('input', array_merge($opts, $options));
    }

    public static function timeFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'time']));
    }

    public static function urlFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'url']));
    }

    public static function weekFieldTag($name, $value = null, $options = []) {
        return static::textFieldTag($name, $value, array_merge($options, ['type' => 'week']));
    }

    private static function convertDirectUploadOptionToUrl($options) {
        if (Arr::pull($options, 'direct_upload') && method_exists(static::class, 'directUploadsUrl')) {
            $options['data-direct-upload-url'] = static::directUploadsUrl();
        }

        return $options;
    }

    private static function deleteSuffix($str, $suffix) {
        return preg_replace('/' . $suffix . '$/', '', $str);
    }

    private static function extraTagsForForm(&$options) {
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

        return $tags;
    }

    private static function htmlOptionsForForm($urlForOptions, $options) {
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

    private static function sanitizeToId($name) {
        return preg_replace('/[^-a-zA-Z0-9:\.]/', '_', str_replace(']', '', $name));
    }

    private static function methodTag($method) {
        return static::tag('input', [
            'type' => 'hidden',
            'name' => '_method',
            'value' => $method ?? 'post',
            'autocomplete' => 'off'
        ], false, false);
    }

    private static function tokenTag($token, $options) {
        if (!static::$protectAgainstForgery) {
            return '';
        }

        return static::tag('input', [
            'type' => 'hidden',
            'name' => '_token',
            'value' => $token ?? csrf_token(),
            'autocomplete' => 'off'
        ], false, false);
    }

    private static function utf8EnforcerTag() {
        return new HtmlString('<input type="hidden" name="utf8" value="&#x2713;" autocomplete="off" />');
    }

    private static function setDefaultDisableWith($value, &$options) {
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
