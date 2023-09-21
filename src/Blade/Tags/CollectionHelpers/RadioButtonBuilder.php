<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

use Illuminate\Support\HtmlString;

class RadioButtonBuilder extends Builder {

    public function radioButton(array $extraHtmlOptions = []): HtmlString {
        $htmlOptions = array_merge($extraHtmlOptions, $this->inputHtmlOptions);
        $htmlOptions['skip_default_ids'] = false;

        return ($this->templateObject)::radioButton($this->objectName, $this->methodName, $this->value, $htmlOptions);
    }
}
