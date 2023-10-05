<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Generator;
use Illuminate\Support\HtmlString;
use SilvertipSoftware\LaravelSupport\Blade\Tags\CollectionHelpers\CheckBoxBuilder;

class CollectionCheckBoxes extends Base {
    use CollectionHelpers {
        CollectionHelpers::hiddenFieldName as originalHiddenFieldName;
    }

    /**
     * @return Generator<int,\stdClass,null,HtmlString>
     */
    public function yieldingRender(bool $yield) {
        $generator = $this->yieldingRenderCollectionFor(CheckBoxBuilder::class, $yield);
        yield from $generator;

        return $generator->getReturn();
    }

    protected function renderComponent(CheckBoxBuilder $builder): string {
        return $builder->checkBox() . $builder->label();
    }

    protected function hiddenFieldName(): string {
        return $this->originalHiddenFieldName() . '[]';
    }
}
