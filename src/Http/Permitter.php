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

    public function require($keys) {
        $keys = (array) $keys;

        $topRules = array_reduce($keys, function ($memo, $key) {
            $memo[$key] = 'required';

            return $memo;
        }, []);

        $filtered = $this->validator->validate(request(), $topRules);

        return new Permitter($keys, $filtered, $this->validator);
    }

    public function permit(array $spec) {
        $fieldRules = $this->permitRulesFor($spec);

        $filtered = Arr::only(
            $this->validator->validate(request(), $fieldRules),
            $this->keys
        );

        if (count($this->keys) == 1 && array_key_exists($this->keys[0], $filtered)) {
            $filtered = $filtered[$this->keys[0]];
        }

        return $filtered;
    }

    public function values() {
        return $this->values;
    }

    protected function permitRulesFor(array $spec) {
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
