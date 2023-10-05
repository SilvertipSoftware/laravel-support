<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\HtmlString;

class HiddenField extends TextField {

    public function render(): HtmlString {
        $this->options['autocomplete'] = 'off';

        return parent::render();
    }
}
