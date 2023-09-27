<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class CheckBox extends Base {
    use Checkable;

    protected $checkedValue;
    protected $uncheckedValue;

    public function __construct($objectName, $methodName, $templateObject, $checkedValue, $uncheckedValue, $options) {
        $this->checkedValue = $checkedValue;
        $this->uncheckedValue = $uncheckedValue;

        parent::__construct($objectName, $methodName, $templateObject, $options);
    }

    public function render() {
        $options = $this->options;
        $options['type'] = 'checkbox';
        $options['value'] = $this->checkedValue;
        if ($this->isInputChecked($options)) {
            $options['checked'] = 'checked';
        }

        if (Arr::get($options, 'multiple')) {
            $this->addDefaultNameAndIdForValue($this->checkedValue, $options);
            Arr::pull($options, 'multiple');
        } else {
            $this->addDefaultNameAndId($options);
        }

        $includeHidden = Arr::pull($options, 'include_hidden', true);
        $checkbox = static::tag('input', $options);

        if ($includeHidden) {
            $hidden = $this->hiddenFieldForCheckbox($options);
            return new HtmlString($hidden . $checkbox);
        }

        return $checkbox;
    }

    protected function hiddenFieldForCheckbox($options) {
        if ($this->uncheckedValue !== false && $this->uncheckedValue !== null) {
            $opts = array_merge(
                Arr::only($options, ['name', 'disabled', 'form']),
                ['type' => 'hidden', 'value' => $this->uncheckedValue, 'autocomplete' => 'off']
            );
            return static::tag('input', $opts);
        }

        return '';
    }

    protected function isChecked($value) {
        if (is_bool($value)) {
            return $value === !!$this->checkedValue;
        } elseif ($value === null) {
            return false;
        } elseif (is_string($value)) {
            return $value === $this->checkedValue;
        } elseif (is_array($value)) {
            return in_array($this->checkedValue, $value);
        } elseif ($value instanceof Collection) {
            return $value->contains($this->checkedValue);
        }

        return $value == $this->checkedValue;
    }
}
