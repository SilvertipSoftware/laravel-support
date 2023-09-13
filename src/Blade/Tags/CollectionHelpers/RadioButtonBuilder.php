<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

class RadioButtonBuilder extends Builder {

    public function radioButton($extraHtmlOptions = []) {
        $htmlOptions = array_merge($extraHtmlOptions, $this->inputHtmlOptions);
        $htmlOptions['skip_default_ids'] = false;

        return ($this->templateObject)::radioButton($this->objectName, $this->methodName, $this->value, $htmlOptions);
    }
}
