<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class ColorField extends TextField {

    public function render() {
        $options = $this->options;
        $options['value'] = Arr::get($options, 'value', $this->validateColorString($this->value()));
        $this->options = $options;

        return parent::render();
    }

    private function validateColorString($str) {
        $regex = '/#[0-9a-fA-F]{6}/';

        if (preg_match($regex, $str)) {
            return strtolower($str);
        }

        return '#000000';
    }
}
