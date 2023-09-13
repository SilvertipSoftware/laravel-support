<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TextField extends Base {
    use Placeholderable;

    public function render() {
        $options = $this->options;
        $options['size'] = Arr::get($options, 'size', Arr::get($options, 'maxlength'));
        $options['type'] = Arr::get($options, 'type', $this->fieldType());
        if ($options['type'] != 'file') {
            $options['value'] = Arr::get($options, 'value', $this->valueBeforeTypeCast());
        }

        $this->addDefaultNameAndId($options);

        return static::tag('input', $options);
    }

    protected function fieldType() {
        $type = str_replace('Field', '', class_basename(get_class($this)));
        return Str::kebab($type);
    }
}
