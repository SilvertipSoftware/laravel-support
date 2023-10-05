<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\HtmlString;

class PasswordField extends TextField {

    public function render(): HtmlString {
        $this->options = array_merge(['value' => null], $this->options);

        return parent::render();
    }
}
