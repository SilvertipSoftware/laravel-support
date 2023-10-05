<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

trait Resourceful {

    protected string $modelRootNamespace = 'App\\Models';

    protected function getSubjectResourceTag(): string {
        return isset($this->resourceTag)
            ? $this->resourceTag
            : Str::singular(Str::snake(Str::replaceLast('Controller', '', class_basename($this))));
    }

    protected function getSubjectResourceClass(): string {
        return isset($this->resourceClass)
            ? $this->resourceClass
            : $this->getActualClassNameFromTag($this->getSubjectResourceTag());
    }

    protected function getActualClassNameFromTag(string $tag): string {
        $clz = $this->modelRootNamespace . '\\' . Str::studly($tag);

        if (class_exists($clz)) {
            return $clz;
        }

        return Model::getActualClassNameForMorph($tag);
    }
}
