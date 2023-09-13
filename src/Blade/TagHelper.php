<?php

namespace SilvertipSoftware\LaravelSupport\Blade;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait TagHelper {

    private static $tagBuilderInstance;

    public static function buildTagValues(...$args) {
        $tagValues = [];

        foreach ($args as $arg) {
            if (is_array($arg)) {
                if (Arr::isAssoc($arg)) {
                    foreach ($arg as $k => $v) {
                        if (!empty($k) && $v) {
                            $tagValues[] = $k;
                        }
                    }
                } else {
                    $tagValues = array_merge($tagValues, static::buildTagValues(...$arg));
                }
            } else {
                if (!empty($arg)) {
                    $tagValues[] = $arg;
                }
            }
        }

        return $tagValues;
    }

    public static function tag($name = null, $options = null, $open = false, $mustEscape = true) {
        if ($name == null) {
            if (static::$tagBuilderInstance == null) {
                static::$tagBuilderInstance = static::newTagBuilder();
            }

            return static::$tagBuilderInstance;
        } else {
            $name = $mustEscape ? Utils::xmlNameEscape($name) : $name;

            return new HtmlString(
                '<' . $name . static::newTagBuilder()->tagOptions($options, $mustEscape)
                . ($open ? '>' : ' />')
            );
        }
    }

    public static function tokenList(...$args) {
        $tokens = array_unique(
            Arr::flatten(
                array_map(function ($v) {
                    return preg_split('/\s+/', $v);
                }, static::buildTagValues($args))
            )
        );

        return implode(' ', array_map(function ($v) {
            return e($v);
        }, $tokens));
    }

    public static function classNames(...$args) {
        return static::tokenList(...$args);
    }

    public static function closeTag($name) {
        return new HtmlString('</' . $name . '>');
    }

    public static function contentTag($name, $content = null, $options = null, $mustEscape = true, $block = null) {
        list($content, $options, $mustEscape, $block) = Utils::determineTagArgs(
            $content,
            $options,
            $mustEscape,
            $block
        );

        $options = $options ?? [];
        $mustEscape = $mustEscape ?? true;

        if ($block && is_callable($block)) {
            return static::newTagBuilder()->contentTagString($name, $block(), $options, $mustEscape);
        }

        return static::newTagBuilder()->contentTagString($name, $content, $options, $mustEscape);
    }

    public static function cdataSection($content = null) {
        if ($content && is_callable($content)) {
            $content = $content();
        }

        $content = preg_replace('/\]\]\>/', ']]]]><![CDATA[>', $content);

        return new HtmlString('<![CDATA[' . $content . ']]>');
    }

    public static function escapeOnce($html) {
        throw new Exception("TBD");
    }

    private static function newTagBuilder() {
        return new TagBuilder(static::class);
    }
}
