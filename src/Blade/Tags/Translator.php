<?php

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

class Translator {

    protected $methodAndValue;
    protected $model;
    protected $objectName;
    protected $scope;

    public function __construct($object, $objectName, $methodAndValue, $scope = null) {
        $this->objectName = preg_replace('/\[(.*)_attributes\]\[\d+\]/', '.$1', $objectName);
        $this->methodAndValue = $methodAndValue;
        $this->scope = $scope;
        $this->model = $object && method_exists($object, 'toModel')
            ? $object->toModel()
            : null;
    }

    public function translate() {
        $key = ($this->scope ? ($this->scope . '.') : '') . $this->objectName . '.' . $this->methodAndValue;
        $trans = trans($key);
        if ($trans === $key) {
            $trans = $this->i18nDefault() ?: $this->humanAttributeName();
        }

        return $trans;
    }

    private function i18nDefault() {
        $trans = null;

        if ($this->model) {
            $modelKey = $this->model->modelName()->i18n_key;
            $key = ($this->scope ? ($this->scope . '.') : '') . $modelKey . '.' . $this->methodAndValue;
            $trans = trans($key);
            if ($trans === $key) {
                $trans = null;
            }
        }

        return $trans;
    }

    private function humanAttributeName() {
        if ($this->model && method_exists($this->model, 'humanAttributeName')) {
            return $this->model->humanAttributeName($this->methodAndValue);
        }

        return null;
    }
}