<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;

class NumberField extends TextField {

    public function render() {
        $options = $this->options;

        $range = Arr::pull($options, 'in', Arr::pull($options, 'within'));
        if (is_array($range)) {
            $options['min'] = $range[0];
            $options['max'] = $range[1];
        }

        $this->options = $options;
        return parent::render();
    }
}
