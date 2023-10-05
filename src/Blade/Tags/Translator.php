<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class Translator {

    protected Model|FluentModel|null $model;
    protected string $objectName;

    public function __construct(
        ?object $object,
        string $objectName,
        protected string $methodAndValue,
        protected ?string $scope = null
    ) {
        $this->objectName = preg_replace('/\[(.*)_attributes\]\[\d+\]/', '.$1', $objectName);
        $this->model = $object && method_exists($object, 'toModel')
            ? $object->toModel()
            : null;
    }

    public function translate(): ?string {
        $key = ($this->scope ? ($this->scope . '.') : '') . $this->objectName . '.' . $this->methodAndValue;
        $trans = trans($key);

        if ($trans === $key) {
            $trans = $this->i18nDefault() ?: $this->humanAttributeName();
        }

        return $trans;
    }

    private function i18nDefault(): string {
        $trans = '';

        if ($this->model) {
            $modelKey = $this->model->modelName()->i18n_key;
            $key = ($this->scope ? ($this->scope . '.') : '') . $modelKey . '.' . $this->methodAndValue;
            $trans = trans($key);
            if ($trans === $key) {
                $trans = '';
            }
        }

        return $trans;
    }

    private function humanAttributeName(): ?string {
        if ($this->model && method_exists($this->model, 'humanAttributeName')) {
            return $this->model->humanAttributeName($this->methodAndValue);
        }

        return null;
    }
}
