<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\MessageBag;
use SilvertipSoftware\LaravelSupport\Blade\TagBuilder;
use SilvertipSoftware\LaravelSupport\Eloquent\ModelContract;
use Stringable;

/**
 * @phpstan-type OptionHash array<string,mixed>
 */
trait ModelInstanceTagHelper {

    /** @var ?Closure(HtmlString,static): HtmlString */
    public static ?Closure $fieldErrorProc = null;

    /**
     * @return string[]
     */
    public function errorMessages(): array {
        return $this->object->errors->get($this->methodName);
    }

    public function instanceContentTag(
        string $name,
        mixed $content = null,
        mixed $options = null,
        mixed $mustEscape = true,
        ?Closure $block = null
    ): HtmlString {
        $baseTag = static::contentTag($name, $content, $options, $mustEscape, $block);

        return $this->isSelectMarkup($name)
            ? $baseTag
            : $this->errorWrapping($baseTag);
    }

    /**
     * @param string|Stringable|OptionHash|Closure $contentOrOptions
     * @param OptionHash $options
     */
    public function instanceLabelTag(
        string $name = null,
        string|Stringable|array|Closure $contentOrOptions = null,
        array|Closure $options = [],
        ?Closure $block = null
    ): HtmlString {
        $baseTag = static::labelTag($name, $contentOrOptions, $options, $block);

        return $this->tagGeneratesErrors($options) && $baseTag instanceof HtmlString
            ? $this->errorWrapping($baseTag)
            : $baseTag;
    }

    /**
     * @param array<string,mixed>|null $options
     */
    public function instanceTag(
        ?string $name = null,
        ?array $options = null,
        bool $open = false,
        bool $mustEscape = true
    ): TagBuilder|HtmlString {
        $baseTag = static::tag($name, $options, $open, $mustEscape);

        return $this->tagGeneratesErrors($options) && $baseTag instanceof HtmlString
            ? $this->errorWrapping($baseTag)
            : $baseTag;
    }

    protected function errorWrapping(HtmlString $baseTag): HtmlString {
        if ($this->objectHasErrors()) {
            $wrapper = static::$fieldErrorProc;
            if (!$wrapper) {
                $wrapper = function (HtmlString $tag, mixed $base): mixed {
                    return static::contentTag('div', $tag, ['class' => 'field_with_errors']);
                };
            }

            return call_user_func_array(
                $wrapper->bindTo(null, $this->templateObject),
                [$baseTag, $this]
            );
        }

        return $baseTag;
    }

    protected function isSelectMarkup(string $type): bool {
        return in_array($type, ['optgroup', 'option']);
    }

    protected function objectHasErrors(): bool {
        $model = $this->object && method_exists($this->object, 'toModel')
            ? $this->object->toModel()
            : $this->object;

        return $model instanceof ModelContract
            && $model->errors instanceof MessageBag
            && !empty($this->errorMessages());
    }

    /**
     * @param array<string,mixed>|null $options
     */
    protected function tagGeneratesErrors(?array $options): bool {
        return Arr::get($options ?: [], 'type') != 'hidden';
    }
}
