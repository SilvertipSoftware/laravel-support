<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Eloquent\Validation;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use SilvertipSoftware\LaravelSupport\Eloquent\FluentModel;
use SilvertipSoftware\LaravelSupport\Eloquent\Model;

class ModelValidator extends Validator {

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @param array<string, mixed> $customAttributes
     */
    public function __construct(
        protected Model|FluentModel $model,
        Translator $translator,
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

    protected function getAttributeFromTranslations($name): string {
        return $this->model->humanAttributeName($name);
    }
}
