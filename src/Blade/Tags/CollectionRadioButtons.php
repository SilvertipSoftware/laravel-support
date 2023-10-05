<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Generator;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\RadioButtonBuilder;

class CollectionRadioButtons extends Base {
    use CollectionHelpers;

    /**
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public function yieldingRender(bool $yield): Generator {
        $generator = $this->yieldingRenderCollectionFor(RadioButtonBuilder::class, $yield);
        yield from $generator;

        return $generator->getReturn();
    }


    protected function renderComponent(RadioButtonBuilder $builder): string {
        return $builder->radioButton() . $builder->label();
    }
}
