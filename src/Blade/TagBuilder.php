<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Stringable;

class TagBuilder {

    protected const BOOLEAN_ATTRIBUTES = [
        'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus',
        'autoplay', 'checked', 'compact', 'controls', 'declare', 'default',
        'defaultchecked', 'defaultmuted', 'defaultselected', 'defer',
        'disabled', 'enabled', 'formnovalidate', 'hidden', 'indeterminate',
        'inert', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nohref',
        'nomodule', 'noresize', 'noshade', 'novalidate', 'nowrap', 'open',
        'pauseonexit', 'playsinline', 'readonly', 'required', 'reversed',
        'scoped', 'seamless', 'selected', 'sortable', 'truespeed',
        'typemustmatch', 'visible',
    ];

    protected const ARIA_PREFIXES = ['aria'];
    protected const DATA_PREFIXES = ['data', 'v'];

    protected const PRE_CONTENT_STRINGS = [
        'textarea' => "\n"
    ];

    protected const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    protected const SVG_SELF_CLOSING_ELEMENTS = [
        'animate', 'animateMotion', 'animateTransform', 'circle', 'ellipse',
        'line', 'path', 'polygon', 'polyline', 'rect', 'set', 'stop', 'use', 'view'
    ];

    public function __construct(protected string $helper) {
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function attributes(array $attributes): HtmlString {
        return new HtmlString(trim($this->tagOptions($attributes)));
    }

    /**
     * @param string|Stringable|array<string,mixed>|null $content
     * @param array<string,mixed> $options
     */
    public function p(string|Stringable|array|null $content = null, array $options = []): HtmlString {
        return $this->tagString('p', $content, $options);
    }

    /**
     * @param string|Stringable|array<string,mixed>|null $content
     * @param array<string,mixed> $options
     */
    public function tagString(
        string $name,
        string|Stringable|array|Closure|null $content = null,
        array|Closure|null $options = [],
        ?Closure $callback = null
    ): HtmlString {
        list($content, $options, $callback) = Utils::determineTagArgs($content, $options, $callback);
        /** @phpstan-assert string|Stringable|null $content */
        /** @phpstan-assert array<string,mixed> $options */
        /** @phpstan-assert ?Closure $callback */

        $options = $options ?? [];
        $mustEscape = Arr::pull($options, 'escape', true);

        if ($callback) {
            $content = $callback();
        }

        $selfClosing = in_array($name, static::SVG_SELF_CLOSING_ELEMENTS);

        if ((in_array($name, static::VOID_ELEMENTS) || $selfClosing) && empty($content)) {
            $closer = $selfClosing ? ' />' : '>';

            return new HtmlString(
                '<' . self::dasherize($name) . $this->tagOptions($options, $mustEscape) . $closer
            );
        } else {
            return $this->contentTagString(self::dasherize($name), $content ?? '', $options, $mustEscape);
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    public function contentTagString(
        string $name,
        string|Stringable|null $content,
        array $options,
        bool $mustEscape = true
    ): HtmlString {
        $tagOptions = $this->tagOptions($options, $mustEscape);

        if ($mustEscape) {
            $name = Utils::xmlNameEscape($name);
            $content = e($content);
        }

        // Remove new lines and carriage returns.
        $content = str_replace(
            ["\n", "\r"],
            "&#10;",
            str_replace(
                ["\r\n", "\n\r"],
                "\n",
                $content ? ('' . $content) : ''
            )
        );

        return new HtmlString(
            '<' . $name . $tagOptions . '>'
            . Arr::get(static::PRE_CONTENT_STRINGS, $name, '') . $content
            . '</' . $name . '>'
        );
    }

    /**
     * @param ?array<string,mixed> $options
     */
    public function tagOptions(?array $options, bool $mustEscape = true): ?string {
        if (empty($options)) {
            return null;
        }

        $output = '';
        $sep = ' ';
        foreach ($options as $key => $value) {
            if (in_array($key, static::DATA_PREFIXES) && is_array($value) && Arr::isAssoc($value)) {
                foreach ($value as $k => $v) {
                    if ($v !== null) {
                        $output .= $sep . $this->prefixTagOption($key, $k, $v, $mustEscape);
                    }
                }
            } elseif (in_array($key, static::ARIA_PREFIXES) && is_array($value) && Arr::isAssoc($value)) {
                foreach ($value as $k => $v) {
                    if ($v !== null) {
                        if (is_array($v)) {
                            $tokens = ($this->helper)::buildTagValues($v);
                            if (empty($tokens)) {
                                continue;
                            }
                            $v = implode(' ', $tokens);
                        }

                        $output .= $sep . $this->prefixTagOption($key, $k, $v, $mustEscape);
                    }
                }
            } elseif (in_array($key, static::BOOLEAN_ATTRIBUTES)) {
                if ($value) {
                    $output .= $sep . $this->booleanTagOption($key);
                }
            } elseif ($value !== null) {
                $output .= $sep . $this->tagOption($key, $value, $mustEscape);
            }
        }

        return $output;
    }

    public function booleanTagOption(string $key): string {
        return $key . '="' . $key . '"';
    }

    public function tagOption(string $key, mixed $value, bool $mustEscape): string {
        $key = $mustEscape ? Utils::xmlNameEscape($key) : $key;
        if (is_array($value)) {
            if ($key == 'class') {
                $value = ($this->helper)::buildTagValues([$value]);
            }
            $value = implode(' ', array_map(function ($v) use ($mustEscape) {
                return $mustEscape ? e($v) : $v;
            }, $value));
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else {
            $value = $mustEscape ? e($value) : ('' . $value);
        }

        $value = preg_replace('/"/', '&quot;', $value);
        return $key . '="' . $value . '"';
    }

    protected function prefixTagOption(string $prefix, string $key, mixed $value, bool $mustEscape): string {
        $key = $prefix . '-' . self::dasherize($key);
        if (!(is_string($value) || is_numeric($value) || $value instanceof HtmlString)) {
            $value = json_encode($value);
        }

        return $this->tagOption($key, $value, $mustEscape);
    }

    /**
     * @param array<mixed> $args
     */
    public function __call(string $method, array $args): HtmlString {
        return $this->tagString($method, ...$args);
    }

    public function __get(string $attr): HtmlString {
        return $this->tagString($attr);
    }

    protected static function dasherize(string $str): string {
        return str_replace('_', '-', $str);
    }
}
