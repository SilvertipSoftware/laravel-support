<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent\Validation;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;

class ModelValidator extends Validator {

    protected $model;
    protected $usesHumanizer;

    public function __construct(
        $model,
        Translator $translator,
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);

        $this->model = $model;
        $this->usesHumanizer = method_exists($this->model, 'humanAttributeName');
    }

    // for old laravel
    public function callModelMethod($method, $attribute, $value, $params) {
        return $this->model->{$method}($attribute, $value, $params);
    }

    protected function getAttributeFromTranslations($name) {
        if ($this->usesHumanizer) {
            return $this->model->humanAttributeName($name);
        }

        return Arr::get($this->translator->get('validation.attributes'), $name);
    }
}
