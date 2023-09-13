<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\RadioButtonBuilder;

class CollectionRadioButtons extends Base {
    use CollectionHelpers;

    public function render($block) {
        return $this->renderCollectionFor(RadioButtonBuilder::class, $block);
    }

    protected function renderComponent($builder) {
        return $builder->radioButton() . $builder->label();
    }
}
