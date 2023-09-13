<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class HiddenField extends TextField {

    public function render() {
        $this->options['autocomplete'] = 'off';

        return parent::render();
    }
}
