<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Blade\FormOptionsHelper;
use SilvertipSoftware\LaravelSupport\Blade\FormTagHelper;
use SilvertipSoftware\LaravelSupport\Blade\TagHelper;

class Base {
    use TagHelper,
        FormTagHelper,
        FormOptionsHelper;

    public $object;

    protected $allowMethodNamesOutsideObject;
    protected $autoIndex = null;
    protected $generateIndexedNames = false;
    protected $methodName;
    protected $objectName;
    protected $options;
    protected $sanitizedMethodName;
    protected $skipDefaultIds;
    protected $templateObject;

    public function __construct($objectName, $methodName, $templateObject, $options = []) {
        $this->methodName = $methodName;
        $this->templateObject = $templateObject;
        $count = 0;
        $indexable = null;

        $this->objectName = preg_replace('/\[\]$/', '', $objectName, -1, $count);
        if (!$count) {
            $this->objectName = preg_replace('/\[\]\]$/', ']', $this->objectName, -1, $count);
            if ($count) {
                $indexable = substr($this->objectName, 0, -1);
            }
        } else {
            $indexable = $this->objectName;
        }

        $this->object = $this->retrieveObject(Arr::pull($options, 'object'));
        $this->skipDefaultIds = Arr::pull($options, 'skip_default_ids');
        $this->allowMethodNamesOutsideObject = Arr::pull($options, 'allow_method_names_outside_object');
        $this->options = $options;

        if ($indexable) {
            $this->generateIndexedNames = true;
            $this->autoIndex = $this->retrieveAutoindex($indexable);
        }
    }

    protected function addDefaultNameAndIdForValue($tagValue, &$options) {
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

    protected function addDefaultNameAndId(&$options) {
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

    protected function addOptions($optionTags, $options, $value = null) {
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

    protected function nameAndIdIndex(&$options) {
        if (array_key_exists('index', $options)) {
            return Arr::pull($options, 'index') ?? '';
        } elseif ($this->generateIndexedNames) {
            return $this->autoIndex ?? '';
        }
    }

    protected function isPlaceholderRequired($htmlOptions) {
        return Arr::get($htmlOptions, 'required')
            && !Arr::get($htmlOptions, 'multiple')
            && Arr::get($htmlOptions, 'size', 1) == 1;
    }

    protected function retrieveAutoindex($str) {
        $object = $this->object ?? call_user_func([$this->templateObject, 'getContextVariable'], $str);
        if ($object && method_exists($object, 'toParam')) {
            return $object->toParam();
        } else {
            throw new RuntimeException('object[] naming needs a toParam() method on ' . $str);
        }
    }

    protected function retrieveObject($object) {
        if ($object) {
            return $object;
        } elseif (call_user_func([$this->templateObject, 'hasContextVariable'], $this->objectName)) {
            return call_user_func([$this->templateObject, 'getContextVariable'], $this->objectName);
        }

        return null;
    }

    protected function sanitizedMethodName() {
        if (!$this->sanitizedMethodName) {
            $this->sanitizedMethodName = preg_replace('/\?$/', '', $this->methodName);
        }

        return $this->sanitizedMethodName;
    }

    protected function sanitizedValue($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $temp = preg_replace('/[\s.]/', '_', $value);
        $temp = preg_replace('/[^-[[:word:]]]/', '', $temp);
        return strtolower($temp);
    }

    protected function selectContentTag($optionTags, $options, $htmlOptions) {
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
        $selTag = static::contentTag('select', $this->addOptions($optionTags, $options, $value), $htmlOptions);

        $hiddenTag = new HtmlString('');
        if (Arr::get($htmlOptions, 'multiple') && Arr::get($options, 'include_hidden', true)) {
            $hiddenTag = static::tag(
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

    protected function tagId($index = false, $namespace = null) {
        return ($this->templateObject)::fieldId(
            $this->objectName,
            $this->methodName,
            [],
            $index,
            $namespace
        );
    }

    protected function tagName($multiple = false, $index = null) {
        return ($this->templateObject)::fieldName(
            $this->objectName,
            $this->sanitizedMethodName(),
            [],
            $multiple,
            $index
        );
    }

    protected function value() {
        if ($this->object) {
            return $this->object->{$this->methodName};
        }
    }

    protected function valueBeforeTypeCast() {
        if ($this->object) {
            return $this->value();
        }
    }

    private function getNameFromOptions($index, $options) {
        return Arr::get($options, 'name', $this->tagName(Arr::get($options, 'multiple'), $index));
    }
}
