<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait TagHelper {

    protected static $tagBuilderInstance;

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
                    return preg_split('/\s+/', '' . $v);
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

        $yield = $block != null;
        $generator = static::yieldingContentTag($name, $content, $options, $mustEscape, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block();
            }
        }

        return $generator->getReturn();
    }

    public static function cdataSection($content = null) {
        if ($content && is_callable($content)) {
            $content = $content();
        }

        $content = preg_replace('/\]\]\>/', ']]]]><![CDATA[>', $content ?: '');

        return new HtmlString('<![CDATA[' . $content . ']]>');
    }

    public static function escapeOnce($html) {
        throw new Exception("TBD");
    }

    public static function yieldingContentTag(
        $name,
        $content = null,
        $options = null,
        $mustEscape = false,
        $yield = true
    ) {
        list($content, $options, $mustEscape) = Utils::determineTagArgs(
            $content,
            $options,
            $mustEscape
        );

        $options = $options ?? [];
        $mustEscape = $mustEscape ?? false;

        if ($yield) {
            $obj = (object)[
                'builder' => null,
                'content' => null
            ];
            yield $obj;
            $content = $obj->content;
        }

        return static::newTagBuilder()->contentTagString($name, $content, $options, $mustEscape);
    }

    protected static function newTagBuilder() {
        return new TagBuilder(static::class);
    }
}
