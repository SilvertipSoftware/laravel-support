<?php

declare(strict_types=1);

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

    public ?MessageBag $errors;

    protected string $genericInvalidMessageKey = 'validation.invalid';

    /** @var array<string,mixed> */
    protected array $validationRules = [];

    /**
     * @param string|array<string, mixed> $rules
     */
    public function addValidationRules(string $attr, string|array $rules): static {
        $existingRules = Arr::get($this->validationRules, $attr, []);

        $this->validationRules[$attr] = array_unique(array_merge($existingRules, (array)$rules));

        return $this;
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function isValid(array $rules = null): bool {
        try {
            $this->validate($rules);
        } catch (ValidationException $vex) {
            // ignore exception
        }

        return $this->errors->isEmpty();
    }

    public function hasError(string $attr): bool {
        return $this->errors->has($attr);
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $ignoreRules
     * @return array<string, mixed>
     */
    public function validate(array $rules = null, array $ignoreRules = []): array {
        $this->errors = new MessageBag();

        $this->fireModelEvent('validating');
        $rules = $rules ?: $this->getValidationRules();
        $ignoreRules = array_merge($ignoreRules, $this->validationRulesToIgnoreForParentRelations());
        $rules = Arr::except($rules, $ignoreRules);

        $ret = $this->validateSelf($rules);
        $this->validateAutosavedRelations();

        $this->fireModelEvent('validated');

        if (!$this->errors->isEmpty()) {
            throw ValidationException::withMessages($this->errors->getMessages());
        }

        return $ret;
    }

    protected function initializeValidation(): void {
        $this->errors = new MessageBag();

        $this->addObservableEvents(['validating', 'validated']);
    }

    protected function mergeErrors(?MessageBag $bag, string $prefix = null): void {
        if ($bag == null || $bag->isEmpty()) {
            return;
        }

        $prefixedMessages = [];
        foreach ($bag->getMessages() as $key => $value) {
            $prefixedMessages[$prefix ? ($prefix.'.'.$key) : $key] = $value;
        }
        $this->errors->merge($prefixedMessages);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getValidationRules(): array {
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

    /**
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    protected function validateSelf(array $rules): array {
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

        return [];
    }

    // overridden by AutosaveRelations
    protected function validationRulesToIgnoreForParentRelations() {
        return [];
    }

    private function buildMethodCallingRule(string $paramStr): MethodCallingRule {
        $params = explode(',', $paramStr);
        $tag = array_shift($params);

        return new MethodCallingRule($this, $tag, $params);
    }
}
