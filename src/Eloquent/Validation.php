<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SilvertipSoftware\LaravelSupport\Eloquent\Validation\MethodCallingRule;
use SilvertipSoftware\LaravelSupport\Eloquent\Validation\ModelValidator;

trait Validation {

    public $errors;

    protected $genericInvalidMessageKey = 'validation.invalid';

    protected $validationRules = [];

    public function addValidationRules($attr, $rules) {
        $existingRules = Arr::get($this->validationRules, $attr, []);

        $this->validationRules[$attr] = array_unique(array_merge($existingRules, (array)$rules));
    }

    public function isValid($rules = null) {
        try {
            $this->validate($rules);
        } catch (ValidationException $vex) {
            // ignore exception
        }

        return $this->errors->isEmpty();
    }

    public function hasError($attr) {
        return $this->errors->has($attr);
    }

    public function validate($rules = null, $ignore_rules = []) {
        $this->errors = new MessageBag();

        $this->fireModelEvent('validating');
        $rules = $rules ?: $this->getValidationRules();
        $ignore_rules = array_merge($ignore_rules, $this->validationRulesToIgnoreForParentRelations());
        $rules = Arr::except($rules, $ignore_rules);

        $ret = $this->validateSelf($rules);
        $this->validateAutosavedRelations();

        $this->fireModelEvent('validated');

        if (!$this->errors->isEmpty()) {
            throw ValidationException::withMessages($this->errors->getMessages());
        }

        return $ret;
    }

    protected function initializeValidation() {
        $this->errors = new MessageBag();

        $this->addObservableEvents(['validating', 'validated']);
    }

    protected function mergeErrors($bag, $prefix = null) {
        if ($bag == null || $bag->isEmpty()) {
            return;
        }

        $prefixedMessages = [];
        foreach ((is_array($bag) ? $bag : $bag->getMessages()) as $key => $value) {
            $prefixedMessages[$prefix ? ($prefix.'.'.$key) : $key] = $value;
        }
        $this->errors->merge($prefixedMessages);

        return $this;
    }

    protected function getValidationRules() {
        $rules = $this->validationRules ?: [];

        foreach ($rules as $attr => &$ruleSet) {
            foreach ($ruleSet as $ix => $ruleDef) {
                if (is_string($ruleDef) && preg_match('/^call:(.+)$/', $ruleDef, $matches)) {
                    $ruleSet[$ix] = $this->buildMethodCallingRule($matches[1]);
                }
            }
        }

        $this->validationRules = $rules;

        return $rules;
    }

    // overridden by AutosaveRelations
    protected function validateAutosavedRelations() {
    }

    protected function validateSelf($rules) {
        try {
            $factory = app(Factory::class);
            $factory->resolver(function ($translator, $data, $rules, $messages, $customAttributes) {
                return new ModelValidator(
                    $this,
                    $translator,
                    $data,
                    $rules,
                    $messages,
                    $customAttributes
                );
            });

            return $factory->validate($this->attributes, $rules);
        } catch (ValidationException $vex) {
            $this->mergeErrors($vex->validator->errors());
        }
    }

    // overridden by AutosaveRelations
    protected function validationRulesToIgnoreForParentRelations() {
        return [];
    }

    private function buildMethodCallingRule($paramStr) {
        $params = explode(',', $paramStr);
        $tag = array_shift($params);

        return new MethodCallingRule($this, $tag, $params);
    }
}
