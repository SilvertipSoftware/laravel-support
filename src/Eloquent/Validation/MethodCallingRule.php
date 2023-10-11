<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent\Validation;

use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class MethodCallingRule implements ImplicitRule {

    protected string $method;

    /**
     * @param array<int, bool|float|int|string> $params
     */
    public function __construct(
        protected Model|FluentModel $model,
        protected string $tag,
        protected array $params
    ) {
        $this->method = $this->methodName($tag);
    }

    public function passes($attribute, $value) {
        return $this->model->{$this->method}($attribute, $value, $this->params);
    }

    /**
     * @return string|array<string,mixed>
     */
    public function message(): string|array {
        $clz = get_class($this->model);
        $prefix = method_exists($clz, 'modelName')
            ? $clz::modelName()->singular
            : str_replace('\\', '', $clz);

        return trans('validation.' . $prefix . '.' . $this->tag);
    }

    protected function methodName(string $tag): string {
        return 'validate' . Str::studly($tag);
    }
}
