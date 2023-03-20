<?php

namespace SilvertipSoftware\LaravelSupport\Http;

use Illuminate\Support\Arr;

class Permitter {

    protected $keys;
    protected $values;
    protected $validator;

    public function __construct($keys, $values, $validator) {
        $this->keys = $keys;
        $this->values = $values;
        $this->validator = $validator;
    }

    public function permit(array $spec) {
        $fieldRules = $this->permitRulesFor($spec);

        $filtered = Arr::only(
            $this->validator->validate(request(), $fieldRules),
            $this->keys
        );

        if (count($this->keys) == 1) {
            $filtered = $filtered[$this->keys[0]];
        }

        return $filtered;
    }

    public function permitRulesFor(array $spec) {
        $rules = [];

        foreach ($this->keys as $key) {
            $rules = array_merge($rules, $this->rulesForNestedPermit($key, $spec));
        }

        return $rules;
    }

    protected function rulesForNestedPermit($prefix, $hash) {
        $rules = [];

        foreach ($hash as $index => $field) {
            if (is_int($index)) {
                $rules[$prefix . '.' . $field] = '';
            } else {
                $subPrefix = $prefix . '.' . $index;
                $rules = array_merge(
                    $rules,
                    $this->rulesForNestedPermit($subPrefix, $field)
                );
            }
        }

        return $rules;
    }
}
