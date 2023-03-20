<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent\Validation;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class MethodCallingRule implements Rule {

    public function __construct($model, $tag, $params) {
        $this->model = $model;
        $this->tag = $tag;
        $this->params = $params;
        $this->method = $this->methodName($tag);
    }

    public function passes($attribute, $value) {
        return $this->model->{$this->method}($attribute, $value, $this->params);
    }

    public function message() {
        $clz = get_class($this->model);
        $prefix = method_exists($clz, 'modelName')
            ? $clz::modelName()->singular
            : str_replace('\\', '', $clz);

        return trans('validation.' . $prefix . '.' . $this->tag, $this->params);
    }

    protected function methodName($tag) {
        return 'validate' . Str::studly($tag);
    }
}
