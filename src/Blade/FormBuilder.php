<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Closure;
use Generator;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;
use Stringable;

/**
 * @phpstan-type HtmlStringGenerator Generator<int,\stdClass,null,HtmlString>
 * @phpstan-type OptionHash array<string,mixed>
 */
class FormBuilder {
    use ModelUtils;

    /** @var string[] */
    public static array $fieldHelpers = [
        'fieldsFor', 'fields', 'label', 'textField', 'passwordField',
        'hiddenField', 'fileField', 'textArea', 'checkBox',
        'radioButton', 'colorField', 'searchField',
        'telephoneField', 'phoneField', 'dateField',
        'timeField', 'datetimeField', 'datetimeLocalField',
        'monthField', 'weekField', 'urlField', 'emailField',
        'numberField', 'rangeField'
    ];

    public string|int|null $index;
    public bool $isMultipart = false;

    /** @var array<string,bool> */
    protected static $booted = [];
    /** @var OptionHash */
    protected array $defaultHtmlOptions;
    /** @var OptionHash */
    protected array $defaultOptions;
    protected bool $emittedHiddenId = false;
    /** @var array<string,int> */
    protected array $nestedChildIndices = [];

    /**
     * @param OptionHash $options
     */
    public function __construct(
        public string $objectName,
        public ?object $object,
        protected string $template,
        public array $options
    ) {
        $this->bootIfNotBooted();

        $this->defaultOptions = $options
            ? Arr::only($options, ['index', 'namespace', 'skip_default_ids', 'allow_method_names_outside_object'])
            : [];

        $this->defaultHtmlOptions = Arr::except(
            $this->defaultOptions,
            ['skip_default_ids', 'allow_method_names_outside_object']
        );

        if (preg_match('/\[\]$/', $this->objectName) === 1) {
            $temp = preg_replace('/\[\]$/', '', $this->objectName);

            if (method_exists($object, 'getRouteKey')) {
                $this->autoIndex = $object->getRouteKey();
            } else {
                throw new RuntimeException('object[] naming needs a getRouteKey() method on ' . $temp);
            }
        }

        $this->index = Arr::get($options, 'index', Arr::get($options, 'child_index'));
    }

    /**
     * @param OptionHash $options
     */
    public function button(mixed $value = null, array $options = [], ?Closure $block = null): HtmlString {
        $yield = $block != null;
        $generator = $this->yieldingButton($value, $options, $yield);
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
    public function checkBox(
        string $method,
        array $options = [],
        string|int|bool $checkedValue = "1",
        string|int|bool|null $uncheckedValue = "0"
    ): HtmlString {
        return ($this->template)::checkBox(
            $this->objectName,
            $method,
            $this->objectifyOptions($options),
            $checkedValue,
            $uncheckedValue
        );
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function collectionCheckBoxes(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        ?Closure $block = null
    ): HtmlString {
        return ($this->template)::collectionCheckBoxes(
            $this->objectName,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $block
        );
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function collectionRadioButtons(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        ?Closure $block = null
    ): HtmlString {
        return ($this->template)::collectionRadioButtons(
            $this->objectName,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $block
        );
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function collectionSelect(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return ($this->template)::collectionSelect(
            $this->objectName,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    /**
     * @param string|string[] $suffixes
     */
    public function fieldId(
        string $method,
        string|array $suffixes = [],
        ?string $namespace = null,
        string|int|null $index = null
    ): string {
        $namespace = $namespace ?? Arr::get($this->options, 'namespace');
        $index = $index ?? Arr::get($this->options, 'index');

        return ($this->template)::fieldId($this->objectName, $method, (array)$suffixes, $index, $namespace);
    }

    /**
     * @param string|string[] $otherNames
     */
    public function fieldName(
        string $method,
        string|array $otherNames = [],
        bool $multiple = false,
        string|int|null $index = null
    ): string {
        $index = $index ?? Arr::get($this->options, 'index');
        $objectName = Arr::get($this->options, 'as', $this->objectName);

        return ($this->template)::fieldName($objectName, $method, (array)$otherNames, $multiple, $index);
    }

    /**
     * @param OptionHash $options
     */
    public function fields(
        ?string $scope = null,
        ?object $model = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        if (!$block) {
            throw new RuntimeException('FormBuilder::fields requires a block');
        }

        $generator = $this->yieldingFields($scope, $model, $options);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder);
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $fieldsOptions
     */
    public function fieldsFor(
        string|object $recordName,
        ?object $recordObject = null,
        array $fieldsOptions = [],
        ?Closure $block = null
    ): HtmlString {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $generator = $this->yieldingFieldsFor($recordName, $recordObject, $fieldsOptions);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder) ?? new HtmlString();
        }

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     */
    public function fileField(string $method, array $options = []): HtmlString {
        $this->setIsMultipart(true);

        return ($this->template)::fileField($this->objectName, $method, $this->objectifyOptions($options));
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function groupedCollectionSelect(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $groupMethod,
        string|int|Closure $groupLabelMethod,
        string|int|Closure $optionKeyMethod,
        string|int|Closure $optionValueMethod,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return ($this->template)::groupedCollectionSelect(
            $this->objectName,
            $method,
            $collection,
            $groupMethod,
            $groupLabelMethod,
            $optionKeyMethod,
            $optionValueMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    /**
     * @param OptionHash $options
     */
    public function hiddenField(string $method, array $options = []): HtmlString {
        if ($method === 'id') {
            $this->emittedHiddenId = true;
        }

        return ($this->template)::hiddenField($this->objectName, $method, $this->objectifyOptions($options));
    }

    public function id(): ?string {
        return Arr::get($this->options, 'html.id', Arr::get($this->options, 'id'));
    }

    /**
     * @param OptionHash $options
     */
    public function label(
        string $method,
        ?string $text = null,
        array $options = [],
        ?Closure $block = null
    ): HtmlString {
        $yield = $block != null;
        $generator = $this->yieldingLabel($method, $text, $options, $yield);
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
    public function radioButton(string $method, mixed $tagValue, array $options = []): HtmlString {
        return ($this->template)::radioButton($this->objectName, $method, $tagValue, $this->objectifyOptions($options));
    }

    /**
     * @param string|array<mixed> $choices
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function select(
        string $method,
        string|Stringable|array $choices = null,
        array $options = [],
        array $htmlOptions = [],
        ?Closure $block = null
    ): HtmlString {
        return ($this->template)::select(
            $this->objectName,
            $method,
            $choices,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $block
        );
    }

    public function setIsMultipart(bool $value): void {
        $this->isMultipart = $value;

        if ($parentBuilder = Arr::get($this->options, 'parent_builder')) {
            $parentBuilder->setIsMultipart($value);
        }
    }

    /**
     * @param string|OptionHash|null $value
     * @param OptionHash $options
     */
    public function submit(string|array|null $value = null, array $options = []): HtmlString {
        if (is_array($value)) {
            $options = $value;
            $value = null;
        }

        $value = $value ?: $this->submitDefaultValue();

        return ($this->template)::submitTag($value, $options);
    }

    /**
     * @param string|string[]|null $priorityZones
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function timeZoneSelect(
        string $method,
        string|array|null $priorityZones = null,
        array $options = [],
        array $htmlOptions = []
    ): HtmlString {
        return ($this->template)::timeZoneSelect(
            $this->objectName,
            $method,
            $priorityZones,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    /**
     * @param array<mixed> $args
     */
    public function __call(string $m, array $args): mixed {
        if (in_array($m, static::$fieldHelpers)) {
            $method = array_shift($args);
            $options = array_shift($args) ?? [];

            return ($this->template)::{$m}($this->objectName, $method, $this->objectifyOptions($options));
        }

        throw new RuntimeException('unknown FormBuilder method ' . $m);
    }

    /**
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     */
    public function weekdaySelect(string $method, array $options = [], array $htmlOptions = []): HtmlString {
        return ($this->template)::weekdaySelect(
            $this->objectName,
            $method,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    protected static function boot(): void {
    }

    protected function bootIfNotBooted(): void {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            static::boot();
        }
    }

    protected function isNestedAttributesRelation(string $name): bool {
        return (method_exists($this->object, 'isNestedAttribute') && $this->object->isNestedAttribute($name))
            || method_exists($this->object, 'set' . Str::studly($name) . 'Attributes');
    }

    protected function nestedChildIndex(string $name): int {
        $ix = Arr::get($this->nestedChildIndices, $name, -1);
        $this->nestedChildIndices[$name] = $ix + 1;

        return $this->nestedChildIndices[$name];
    }

    /**
     * @param OptionHash $options
     * @return OptionHash
     */
    protected function objectifyOptions(array $options): array {
        $result = array_merge($this->defaultOptions, $options);
        $result['object'] = $this->object;

        return $result;
    }

    protected function submitDefaultValue(): string {
        $object = static::convertToModel($this->object);
        $key = $object ? ($object->exists ? 'update' : 'create') : 'submit';

        if (method_exists($object, 'modelName')) {
            $model = $object::modelName()->human;
        } else {
            $model = StrUtils::humanize($this->objectName);
        }

        $possibleKeys = [];
        if (method_exists($object, 'modelName') && $this->objectName == strtolower($model)) {
            $possibleKeys[] = 'helpers.submit.' . $object->modelName()->i18n_key . '.' . $key;
        } else {
            $possibleKeys[] = 'helpers.submit.' . $this->objectName . '.' . $key;
        }
        $possibleKeys[] = 'helpers.submit.' . $key;
        $fallback = StrUtils::humanize($key) . ' ' . $model;

        return StrUtils::translate($possibleKeys, $fallback);
    }

    /**
     * @param string|Stringable|OptionHash|null $value
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public function yieldingButton(
        string|Stringable|array|null $value = null,
        array $options = [],
        bool $yield = true
    ): Generator {
        if (is_array($value)) {
            $options = $value;
            $value = null;
        } elseif ($value instanceof Stringable) {
            $opts = [
                'name' => $this->fieldName($value->__toString()),
                'id' => $this->fieldId($value->__toString())
            ];
            $options = array_merge($opts, $options);
            $value = null;
        }
        $value = $value ?: $this->submitDefaultValue();

        if ($yield) {
            $obj = (object)[
                'builder' => $value,
                'content' => ''
            ];
            yield $obj;
            $value = new HtmlString($obj->content);
        }

        $formmethod = Arr::get($options, 'formmethod');
        if ($formmethod
            && !preg_match('/post|get/i', $formmethod)
            && !Arr::has($options, 'name')
            && !Arr::has($options, 'value')
        ) {
            $options = array_merge($options, ['formmethod' => 'post', 'name' => '_method', 'value' => $formmethod]);
        }

        return ($this->template)::buttonTag($value, $options);
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     * @return HtmlStringGenerator
     */
    public function yieldingCollectionCheckBoxes(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        bool $yield = true
    ): Generator {
        $generator = ($this->template)::yieldingCollectionCheckBoxes(
            $this->objectName,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $yield
        );
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|Collection<array-key, mixed>|QueryBuilder|null $collection
     * @param OptionHash $options
     * @param OptionHash $htmlOptions
     * @return HtmlStringGenerator
     */
    public function yieldingCollectionRadioButtons(
        string $method,
        array|Collection|QueryBuilder|null $collection,
        string|int|Closure $valueMethod,
        string|int|Closure $textMethod,
        array $options = [],
        array $htmlOptions = [],
        bool $yield = true
    ): Generator {
        $generator = ($this->template)::yieldingCollectionRadioButtons(
            $this->objectName,
            $method,
            $collection,
            $valueMethod,
            $textMethod,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $yield
        );
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param string|OptionHash|null $text
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public function yieldingLabel(
        string $method,
        string|array|null $text = null,
        array $options = [],
        bool $yield = true
    ): Generator {
        $generator = ($this->template)::yieldingLabel(
            $this->objectName,
            $method,
            $text,
            $this->objectifyOptions($options),
            $yield
        );
        yield from $generator;
        return $generator->getReturn();
    }

    /**
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    public function yieldingFields(?string $scope = null, ?object $model = null, array $options = []): Generator {
        $options['allow_method_names_outside_object'] = true;
        $options['skip_default_ids'] = ($this->template)::$formWithGeneratesIds;

        $generator = $this->yieldingFieldsFor($scope ?: $model, $model, $options);
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|object|null $recordObject
     * @param OptionHash $fieldsOptions
     * @return HtmlStringGenerator
     */
    public function yieldingFieldsFor(
        string|object $recordName,
        array|object|null $recordObject = null,
        array $fieldsOptions = []
    ): Generator {
        if (is_array($recordObject) && Arr::isAssoc($recordObject)) {
            $fieldsOptions = $recordObject;
            $recordObject = null;
        }

        $fieldsOptions['builder'] = Arr::get($fieldsOptions, 'builder', $this->options['builder'] ?? null);
        $fieldsOptions['namespace'] = Arr::get($fieldsOptions, 'namespace', $this->options['namespace'] ?? null);
        $fieldsOptions['parent_builder'] = $this;

        if (is_string($recordName)) {
            if ($this->isNestedAttributesRelation($recordName)) {
                $generator = $this->yieldingFieldsForWithNestedAttributes($recordName, $recordObject, $fieldsOptions);
                yield from $generator;
                return $generator->getReturn();
            }
        } else {
            $recordObject = ($this->template)::objectForFormBuilder($recordName);
            $recordName = static::modelNameFrom($recordObject)->param_key;
        }

        $objectName = $this->objectName;
        $this->index = null;
        if (Arr::has($this->options, 'index')) {
            $this->index = $this->options['index'];
        } elseif (!empty($this->autoIndex)) {
            $objectName = preg_replace('/\[\]$/', '', $objectName);
            $this->index = $this->autoIndex;
        }

        if ($this->index !== null) {
            $recordName = $objectName . '[' . $this->index . '][' . $recordName . ']';
        } elseif (preg_match('/\[\]$/', $recordName)) {
            $recordName = $objectName . '[' . substr($recordName, 0, -2) . ']'
                . '[' . $recordObject->getKey() . ']';
        } else {
            $recordName = $objectName . '[' . $recordName . ']';
        }
        $fieldsOptions['child_index'] = $this->index;

        $generator = ($this->template)::yieldingFieldsFor($recordName, $recordObject, $fieldsOptions);
        yield from $generator;

        return $generator->getReturn();
    }

    /**
     * @param OptionHash $fieldsOptions
     * @return HtmlStringGenerator
     */
    protected function yieldingFieldsForNestedModel(string $name, ?object $object, array $fieldsOptions): Generator {
        $object = static::convertToModel($object);
        $emitHiddenId = $object && $object->exists
            && Arr::get($fieldsOptions, 'include_id', Arr::get($this->options, 'include_id', true));

        $generator = ($this->template)::yieldingFieldsFor($name, $object, $fieldsOptions);
        foreach ($generator as $obj) {
            $obj2 = (object)[
                'builder' => $obj->builder,
                'content' => null
            ];
            yield $obj2;
            $output = $obj2->content;
            // @phpstan-ignore-next-line
            if ($output && $emitHiddenId && !$obj->builder->emittedHiddenId) {
                $output .= $obj->builder->hiddenField('id');
            }
            $obj->content = $output;
        }

        return $generator->getReturn();
    }

    /**
     * @param array<mixed>|Model|Collection<array-key, mixed>|null $association
     * @param OptionHash $options
     * @return HtmlStringGenerator
     */
    protected function yieldingFieldsForWithNestedAttributes(
        string $associationName,
        array|Model|Collection|null $association,
        array $options
    ): Generator {
        $name = $this->objectName . '[' . $associationName . '_attributes]';
        $association = static::convertToModel($association);

        if ($association instanceof Model) {
            if (method_exists($this->object, $associationName)) {
                $relationClass = get_class($this->object->{$associationName}());
                if (($this->template)::isManyRelation($relationClass)) {
                    $association = [$association];
                }
            }
        } elseif (!(is_array($association) || $association instanceof Collection)) {
            $association = $this->object->{$associationName};
        }

        if ($association instanceof Collection) {
            $association = $association->all();
        }

        if (is_array($association)) {
            $explicitChildIndex = Arr::get($options, 'child_index');
            $buffer = '';

            foreach ($association as $index => $child) {
                if ($explicitChildIndex) {
                    if (is_callable($explicitChildIndex)) {
                        $options['child_index'] = $explicitChildIndex();
                    }
                } else {
                    $options['child_index'] = $this->nestedChildIndex($name);
                }

                $generator = $this->yieldingFieldsForNestedModel(
                    $name . '[' . Arr::get($options, 'child_index') . ']',
                    $child,
                    $options,
                );
                yield from $generator;
                $content = $generator->getReturn();

                $buffer .= $content;
            }

            return new HtmlString($buffer);
        } else {
            $generator = $this->yieldingFieldsForNestedModel($name, $association, $options);
            yield from $generator;

            return $generator->getReturn();
        }
    }
}
