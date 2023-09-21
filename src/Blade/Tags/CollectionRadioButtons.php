<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\RadioButtonBuilder;

class CollectionRadioButtons extends Base {
    use CollectionHelpers;

    public function yieldingRender($yield) {
        $generator = $this->yieldingRenderCollectionFor(RadioButtonBuilder::class, $yield);
        yield from $generator;

        return $generator->getReturn();
    }


    protected function renderComponent($builder) {
        return $builder->radioButton() . $builder->label();
    }
}
