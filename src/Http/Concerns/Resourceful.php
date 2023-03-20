<?php

namespace SilvertipSoftware\LaravelSupport\Http\Concerns;

use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

trait Resourceful {

    protected $modelRootNamespace = 'App\\Models';

    protected function getSubjectResourceTag() {
        return isset($this->resourceTag)
            ? $this->resourceTag
            : Str::singular(Str::snake(Str::replaceLast('Controller', '', class_basename($this))));
    }

    protected function getSubjectResourceClass() {
        return isset($this->resourceClass)
            ? $this->resourceClass
            : $this->getActualClassNameFromTag($this->getSubjectResourceTag());
    }

    protected function getActualClassNameFromTag($tag) {
        $clz = $this->modelRootNamespace . '\\' . Str::studly($tag);

        if (class_exists($clz)) {
            return $clz;
        }

        return Model::getActualClassNameForMorph($tag);
    }
}
