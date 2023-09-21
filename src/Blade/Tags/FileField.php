<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class FileField extends TextField {

    public function render() {
        $includeHidden = Arr::pull($this->options, 'include_hidden');
        $options = $this->options;
        $this->addDefaultNameAndId($options);

        if (Arr::get($options, 'multiple') && $includeHidden) {
            return new HtmlString($this->hiddenFieldForMultipleFile($options) . parent::render());
        }

        return parent::render();
    }

    private function hiddenFieldForMultipleFile($options) {
        $opts = [
            'name' => $options['name'],
            'type' => 'hidden',
            'value' => '',
            'autocomplete' => 'off'
        ];

        return static::tag('input', $opts);
    }
}
