<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Blade\FormOptionsHelper;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use SilvertipSoftware\LaravelSupport\Blade\ModelInstanceTagHelper;
use SilvertipSoftware\LaravelSupport\Blade\TagHelper;
use Stringable;

class Base {
    use TagHelper,
        FormTagHelper,
        FormOptionsHelper,
        ModelInstanceTagHelper;

    public ?object $object;

    protected int|string|null $autoIndex = null;
    protected bool $generateIndexedNames = false;
    protected string $objectName;
    /** @var array<string,mixed> */
    protected array$options;
    protected ?string $sanitizedMethodName = null;
    protected bool $skipDefaultIds;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        ?string $objectName,
        protected string $methodName,
        protected string $templateObject,
        array $options = []
    ) {
        $count = 0;
        $indexable = null;

        $this->objectName = preg_replace('/\[\]$/', '', $objectName ?: '', -1, $count);
        if (!$count) {
            $this->objectName = preg_replace('/\[\]\]$/', ']', $this->objectName, -1, $count);
            if ($count) {
                $indexable = substr($this->objectName, 0, -1);
            }
        } else {
            $indexable = $this->objectName;
        }

        $this->object = $this->retrieveObject(Arr::pull($options, 'object'));
        Arr::pull($options, 'allow_method_names_outside_object');
        $this->skipDefaultIds = Arr::pull($options, 'skip_default_ids', false);
        $this->options = $options;

        if ($indexable) {
            $this->generateIndexedNames = true;
            $this->autoIndex = $this->retrieveAutoindex($indexable);
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function addDefaultNameAndIdForValue(string|bool|int|float|null $tagValue, array &$options): void {
        if ($tagValue === null) {
            $this->addDefaultNameAndId($options);
        } else {
            $specifiedId = Arr::get($options, 'id');
            $this->addDefaultNameAndId($options);

            if (empty($specifiedId) && !empty(Arr::get($options, 'id'))) {
                $options['id'] = $options['id'] . '_' . $this->sanitizedValue($tagValue);
            }
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function addDefaultNameAndId(array &$options): void {
        $index = $this->nameAndIdIndex($options);
        $options['name'] = $this->getNameFromOptions($index, $options);

        if (!$this->skipDefaultIds) {
            $options['id'] = Arr::get($options, 'id', $this->tagId($index, Arr::pull($options, 'namespace')));

            $namespace = Arr::pull($options, 'namespace');
            if ($namespace) {
                $options['id'] = $options['id'] ? $namespace . '_' . $options['id'] : $namespace;
            }
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function addOptions(string|HtmlString $optionTags, array $options, mixed $value = null): HtmlString {
        $blank = Arr::get($options, 'include_blank');
        $content = null;

        if ($blank) {
            if (is_string($blank)) {
                $content = $blank;
            }
            $label = !$content ? ' ' : null;
            $optionTags = static::tag()->contentTagString('option', $content, ['value' => '', 'label' => $label])
                . "\n"
                . $optionTags;
        }

        if (!$value && Arr::get($options, 'prompt')) {
            $tagOptions = ['value' => ''];
            if (Arr::get($options, 'disabled') === '') {
                $tagOptions['disabled'] = true;
            }
            if (Arr::get($options, 'selected') === '') {
                $tagOptions['selected'] = true;
            }
            $optionTags = static::tag()->contentTagString(
                'option',
                static::promptText(Arr::get($options, 'prompt')),
                $tagOptions
            ) . "\n" . $optionTags;
        }

        return new HtmlString($optionTags);
    }

    /**
     * @param array<string,mixed> $options
     */
    protected function nameAndIdIndex(&$options): int|string|null {
        if (array_key_exists('index', $options)) {
            return Arr::pull($options, 'index') ?? '';
        } elseif ($this->generateIndexedNames) {
            return $this->autoIndex ?? '';
        }

        return null;
    }

    /**
     * @param array<string,mixed> $htmlOptions
     */
    protected function isPlaceholderRequired($htmlOptions): bool {
        return Arr::get($htmlOptions, 'required')
            && !Arr::get($htmlOptions, 'multiple')
            && Arr::get($htmlOptions, 'size', 1) == 1;
    }

    protected function retrieveAutoindex(string $str): int|string|null {
        $object = $this->object;
        if ($object && method_exists($object, 'toParam')) {
            return $object->toParam();
        } else {
            throw new RuntimeException('object[] naming needs a toParam() method on ' . $str);
        }
    }

    protected function retrieveObject(?object $object): ?object {
        if ($object) {
            return $object;
        }
        // removed: getting instance variable from name

        return null;
    }

    protected function sanitizedMethodName(): string {
        if (!$this->sanitizedMethodName) {
            $this->sanitizedMethodName = preg_replace('/\?$/', '', $this->methodName);
        }

        return $this->sanitizedMethodName;
    }

    protected function sanitizedValue(string|bool|int|float|Stringable|null $value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $temp = preg_replace('/[\s.]/', '_', '' . $value);
        $temp = preg_replace('/[^-[[:word:]]]/', '', $temp);

        return strtolower($temp);
    }

    /**
     * @param array<string,mixed> $options
     * @param array<string,mixed> $htmlOptions
     */
    protected function selectContentTag(string|HtmlString $optionTags, array $options, array $htmlOptions): HtmlString {
        $this->addDefaultNameAndId($htmlOptions);

        if ($this->isPlaceholderRequired($htmlOptions)) {
            if (Arr::get($options, 'include_blank') === false) {
                throw new RuntimeException('include_blank cannot be false for a required field');
            }
            if (!Arr::get($options, 'prompt')) {
                $options['include_blank'] = Arr::get($options, 'include_blank') ?: true;
            }
        }

        $value = Arr::get($options, 'selected', $this->value());
        $selTag = $this->instanceContentTag('select', $this->addOptions($optionTags, $options, $value), $htmlOptions);

        $hiddenTag = new HtmlString('');
        if (Arr::get($htmlOptions, 'multiple') && Arr::get($options, 'include_hidden', true)) {
            $hiddenTag = $this->instanceTag(
                'input',
                [
                    'disabled' => Arr::get($htmlOptions, 'disabled'),
                    'name' => Arr::get($htmlOptions, 'name'),
                    'type' => 'hidden',
                    'value' => '',
                    'autocomplete' => 'off'
                ]
            );
        }

        return new HtmlString($hiddenTag->toHtml() . $selTag->toHtml());
    }

    protected function tagId(string|int|bool|null $index = false, ?string $namespace = null): string {
        return ($this->templateObject)::fieldId(
            $this->objectName,
            $this->methodName,
            [],
            $index,
            $namespace
        );
    }

    protected function tagName(bool $multiple = false, string|int|null $index = null): string {
        return ($this->templateObject)::fieldName(
            $this->objectName,
            $this->sanitizedMethodName(),
            [],
            $multiple,
            $index
        );
    }

    protected function value(): mixed {
        if ($this->object) {
            return $this->object->{$this->methodName};
        }

        return null;
    }

    protected function valueBeforeTypeCast(): mixed {
        if ($this->object) {
            return $this->value();
        }

        return null;
    }

    /**
     * @param array<string,mixed> $options
     */
    private function getNameFromOptions(string|int|null $index, array $options): ?string {
        return Arr::get($options, 'name', $this->tagName(Arr::get($options, 'multiple', false), $index));
    }
}
