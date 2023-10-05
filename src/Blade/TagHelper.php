<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Exception;
use Generator;
use Stringable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait TagHelper {

    protected static ?TagBuilder $tagBuilderInstance = null;

    /**
     * @return array<mixed>
     */
    public static function buildTagValues(mixed ...$args): array {
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

    /**
     * @param array<string,mixed>|null $options
     */
    public static function tag(
        ?string $name = null,
        ?array $options = null,
        bool $open = false,
        bool $mustEscape = true
    ): TagBuilder|HtmlString {
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

    public static function tokenList(mixed ...$args): string {
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

    public static function classNames(mixed ...$args): string {
        return static::tokenList(...$args);
    }

    public static function closeTag(string $name): HtmlString {
        return new HtmlString('</' . $name . '>');
    }

    public static function contentTag(
        string $name,
        mixed $content = null,
        mixed $options = null,
        mixed $mustEscape = true,
        ?Closure $block = null
    ): HtmlString {
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

    public static function cdataSection(string|HtmlString|null $content = null): HtmlString {
        if ($content && is_callable($content)) {
            $content = $content();
        }

        $content = preg_replace('/\]\]\>/', ']]]]><![CDATA[>', $content ?: '');

        return new HtmlString('<![CDATA[' . $content . ']]>');
    }

    public static function escapeOnce(string|HtmlString|null $html): HtmlString {
        throw new Exception("TBD");
    }

    /**
     * @param string|Stringable|array<mixed>|null $content
     * @param array<string,mixed> $options
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public static function yieldingContentTag(
        string $name,
        string|Stringable|array|null $content = null,
        array $options = null,
        bool $mustEscape = false,
        bool $yield = true
    ): Generator {
        list($content, $options, $mustEscape) = Utils::determineTagArgs(
            $content,
            $options,
            $mustEscape
        );
        /** @phpstan-assert string|HtmlString|null $content */
        /** @phpstan-assert array<string,mixed> $options */

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

    protected static function newTagBuilder(): TagBuilder {
        return new TagBuilder(static::class);
    }
}
