<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\CheckBoxBuilder;

class CollectionCheckBoxes extends Base {
    use CollectionHelpers {
        CollectionHelpers::hiddenFieldName as originalHiddenFieldName;
    }

    public function render($block) {
        return $this->renderCollectionFor(CheckBoxBuilder::class, $block);
    }

    protected function renderComponent($builder) {
        return $builder->checkBox() . $builder->label();
    }

    protected function hiddenFieldName() {
        return $this->originalHiddenFieldName() . '[]';
    }
}
