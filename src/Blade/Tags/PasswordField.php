<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class PasswordField extends TextField {

    public function render() {
        $this->options = array_merge(['value' => null], $this->options);

        return parent::render();
    }
}
