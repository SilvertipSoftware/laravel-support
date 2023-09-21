<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

class FormBuilder {
    use ModelUtils;

    public static $fieldHelpers = [
        'fieldsFor', 'fields', 'label', 'textField', 'passwordField',
        'hiddenField', 'fileField', 'textArea', 'checkBox',
        'radioButton', 'colorField', 'searchField',
        'telephoneField', 'phoneField', 'dateField',
        'timeField', 'datetimeField', 'datetimeLocalField',
        'monthField', 'weekField', 'urlField', 'emailField',
        'numberField', 'rangeField'
    ];

    public $index;
    public $isMultipart;
    public $object;
    public $objectName;
    public $options;

    protected $defaultHtmlOptions;
    protected $defaultOptions;
    protected $emittedHiddenId = false;
    protected $nestedChildIndices = [];
    protected $template;

    public function __construct($objectName, $object, $template, $options) {
        $this->objectName = $objectName;
        $this->object = $object;
        $this->template = $template;
        $this->options = $options;

        $this->defaultOptions = $options
            ? Arr::only($options, ['index', 'namespace', 'skip_default_ids', 'allow_method_names_outside_object'])
            : [];

        $this->defaultHtmlOptions = Arr::except(
            $this->defaultOptions,
            ['skip_default_ids', 'allow_method_names_outside_object']
        );

        if (preg_match('/\[\]$/', '' . $this->objectName) === 1) {
            $temp = preg_replace('/\[\]$/', '', $this->objectName);

            if (method_exists($object, 'getRouteKey')) {
                $this->autoIndex = $object->getRouteKey();
            } else {
                throw new RuntimeException('object[] naming needs a getRouteKey() method on ' . $temp);
            }
        }

        $this->isMultipart = null;
        $this->index = Arr::get($options, 'index', Arr::get($options, 'child_index'));
    }

    public function button($value = null, $options = [], $block = null) {
        $yield = $block != null;
        $generator = $this->yieldingButton($value, $options, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }
        return $generator->getReturn();
    }

    public function checkBox($method, $options = [], $checkedValue = "1", $uncheckedValue = "0") {
        return ($this->template)::checkBox(
            $this->objectName,
            $method,
            $this->objectifyOptions($options),
            $checkedValue,
            $uncheckedValue
        );
    }

    public function collectionCheckBoxes(
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = [],
        $block = null
    ) {
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

    public function collectionRadioButtons(
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = [],
        $block = null
    ) {
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

    public function collectionSelect(
        $method,
        $collection,
        $valueMethod,
        $textMethod,
        $options = [],
        $htmlOptions = []
    ) {
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

    public function fieldId($method, $suffixes = [], $namespace = null, $index = null) {
        $namespace = $namespace ?? Arr::get($this->options, 'namespace');
        $index = $index ?? Arr::get($this->options, 'index');

        return ($this->template)::fieldId($this->objectName, $method, (array)$suffixes, $index, $namespace);
    }

    public function fieldName($method, $otherNames = [], $multiple = false, $index = null) {
        $index = $index ?? Arr::get($this->options, 'index');
        $objectName = Arr::get($this->options, 'as', $this->objectName);

        return ($this->template)::fieldName($objectName, $method, (array)$otherNames, $multiple, $index);
    }

    public function fields($scope = null, $model = null, $options = [], $block = null) {
        if (!$block) {
            throw new RuntimeException('FormBuilder::fields requires a block');
        }

        $generator = $this->yieldingFields($scope, $model, $options);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder);
        }

        return $generator->getReturn();
    }

    public function fieldsFor($recordName, $recordObject = null, $fieldsOptions = [], $block = null) {
        if (!is_callable($block)) {
            throw new RuntimeException('fieldsFor requires a callback');
        }

        $generator = $this->yieldingFieldsFor($recordName, $recordObject, $fieldsOptions);
        foreach ($generator as $obj) {
            $obj->content = $block($obj->builder);
        }

        return $generator->getReturn();
    }

    public function fileField($method, $options = []) {
        $this->setIsMultipart(true);

        return ($this->template)::fileField($this->objectName, $method, $this->objectifyOptions($options));
    }

    public function groupedCollectionSelect(
        $method,
        $collection,
        $groupMethod,
        $groupLabelMethod,
        $optionKeyMethod,
        $optionValueMethod,
        $options = [],
        $htmlOptions = []
    ) {
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

    public function hiddenField($method, $options = []) {
        if ($method === 'id') {
            $this->emittedHiddenId = true;
        }

        return ($this->template)::hiddenField($this->objectName, $method, $this->objectifyOptions($options));
    }

    public function id() {
        return Arr::get($this->options, 'html.id', Arr::get($this->options, 'id'));
    }

    public function label($method, $text = null, $options = [], $block = null) {
        $yield = $block != null;
        $generator = $this->yieldingLabel($method, $text, $options, $yield);
        if ($yield) {
            foreach ($generator as $obj) {
                $obj->content = $block($obj->builder);
            }
        }

        return $generator->getReturn();
    }

    public function radioButton($method, $tagValue, $options = []) {
        return ($this->template)::radioButton($this->objectName, $method, $tagValue, $this->objectifyOptions($options));
    }

    public function select($method, $choices = null, $options = [], $htmlOptions = [], $block = null) {
        return ($this->template)::select(
            $this->objectName,
            $method,
            $choices,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions),
            $block
        );
    }

    public function setIsMultipart($value) {
        $this->isMultipart = $value;

        if ($parentBuilder = Arr::get($this->options, 'parent_builder')) {
            $parentBuilder->setIsMultipart($value);
        }
    }

    public function submit($value = null, $options = []) {
        if (is_array($value)) {
            $options = $value;
            $value = null;
        }

        $value = $value ?: $this->submitDefaultValue();

        return ($this->template)::submitTag($value, $options);
    }

    public function timeZoneSelect($method, $priorityZones = null, $options = [], $htmlOptions = []) {
        return ($this->template)::timeZoneSelect(
            $this->objectName,
            $method,
            $priorityZones,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    public function __call($m, $args) {
        if (in_array($m, static::$fieldHelpers)) {
            $method = array_shift($args);
            $options = array_shift($args) ?? [];

            return ($this->template)::{$m}($this->objectName, $method, $this->objectifyOptions($options));
        }

        throw new RuntimeException('unknown FormBuilder method ' . $m);
    }

    public function weekdaySelect($method, $options = [], $htmlOptions = []) {
        return ($this->template)::weekdaySelect(
            $this->objectName,
            $method,
            $this->objectifyOptions($options),
            array_merge($this->defaultHtmlOptions, $htmlOptions)
        );
    }

    protected function isNestedAttributesRelation($name) {
        return (method_exists($this->object, 'isNestedAttribute') && $this->object->isNestedAttribute($name))
            || method_exists($this->object, 'set' . Str::studly($name) . 'Attributes');
    }

    protected function nestedChildIndex($name) {
        $ix = Arr::get($this->nestedChildIndices, $name, -1);
        $this->nestedChildIndices[$name] = $ix + 1;

        return $this->nestedChildIndices[$name];
    }

    protected function objectifyOptions($options) {
        $result = array_merge($this->defaultOptions, $options);
        $result['object'] = $this->object;

        return $result;
    }

    protected function submitDefaultValue() {
        $object = static::convertToModel($this->object);
        $key = $object ? ($object->exists ? 'update' : 'create') : 'submit';

        if (method_exists($object, 'modelName')) {
            $model = $object->modelName()->human;
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

    public function yieldingButton($value = null, $options = [], $yield = true) {
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

    public function yieldingLabel($method, $text = null, $options = [], $yield = true) {
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

    public function yieldingFields($scope = null, $model = null, $options = []) {
        $options['allow_method_names_outside_object'] = true;
        $options['skip_default_ids'] = ($this->template)::$formWithGeneratesIds;

        $generator = $this->yieldingFieldsFor($scope ?: $model, $model, $options);
        yield from $generator;

        return $generator->getReturn();
    }

    public function yieldingFieldsFor($recordName, $recordObject = null, $fieldsOptions = []) {
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

    protected function yieldingFieldsForNestedModel($name, $object, $fieldsOptions) {
        $object = static::convertToModel($object);
        $emitHiddenId = $object && $object->exists
            && Arr::get($fieldsOptions, 'include_id', Arr::get($this->options, 'include_id', true));

        $fn = function ($f) use ($emitHiddenId) {
            $obj = (object)[
                'builder' => $f,
                'content' => null
            ];
            yield $obj;
            $output = $obj->content;

            if ($output && $emitHiddenId && !$f->emittedHiddenId) {
                $output .= ($this->template)::hiddenField('id');
            }

            return $output;
        };

        $generator = ($this->template)::yieldingFieldsFor($name, $object, $fieldsOptions);
        foreach ($generator as $obj) {
            $obj2 = (object)[
                'builder' => $obj->builder,
                'content' => null
            ];
            yield $obj2;
            $obj->content = $obj2->content;
        }

        return $generator->getReturn();
    }

    protected function yieldingFieldsForWithNestedAttributes($associationName, $association, $options) {
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

                if ($content) {
                    $buffer .= $content;
                }
            }

            return new HtmlString($buffer);
        } else {
            $generator = $this->yieldingFieldsForNestedModel($name, $association, $options);
            yield from $generator;

            return $generator->getReturn();
        }
    }
}
