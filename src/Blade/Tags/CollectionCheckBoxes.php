<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\CheckBoxBuilder;

class CollectionCheckBoxes extends Base {
    use CollectionHelpers {
        CollectionHelpers::hiddenFieldName as originalHiddenFieldName;
    }

    public function yieldingRender($yield) {
        $generator = $this->yieldingRenderCollectionFor(CheckBoxBuilder::class, $yield);
        yield from $generator;

        return $generator->getReturn();
    }

    protected function renderComponent($builder) {
        return $builder->checkBox() . $builder->label();
    }

    protected function hiddenFieldName() {
        return $this->originalHiddenFieldName() . '[]';
    }
}
