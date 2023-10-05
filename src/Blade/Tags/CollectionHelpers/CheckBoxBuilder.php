<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers;

use Illuminate\Support\HtmlString;

class CheckBoxBuilder extends Builder {

    /**
     * @param array<string,mixed> $extraHtmlOptions
     */
    public function checkBox(array $extraHtmlOptions = []): HtmlString {
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
