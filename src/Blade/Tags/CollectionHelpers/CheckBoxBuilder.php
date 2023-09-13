<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

class CheckBoxBuilder extends Builder {

    public function checkBox($extraHtmlOptions = []) {
        $htmlOptions = array_merge($extraHtmlOptions, $this->inputHtmlOptions);
        $htmlOptions['multiple'] = true;
        $htmlOptions['skip_default_ids'] = false;

        return ($this->templateObject)::checkBox(
            $this->objectName,
            $this->methodName,
            $htmlOptions,
            $this->value,
            null
        );
    }
}
