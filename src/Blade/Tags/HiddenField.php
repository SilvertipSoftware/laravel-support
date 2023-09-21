<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class HiddenField extends TextField {

    public function render() {
        $this->options['autocomplete'] = 'off';

        return parent::render();
    }
}
