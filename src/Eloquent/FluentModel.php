<?php

namespace SilvertipSoftware\LaravelSupport\Eloquent;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use SilvertipSoftware\LaravelSupport\Libs\StrongParameters\Parameters;

class FluentModel extends Fluent implements ModelContract {
    use HasEvents,
        Naming,
        Translation,
        Validation;

    /**
     * @param array<string,mixed>|Arrayable $attributes
     */
    public function __construct(array|Arrayable $attributes = []) {
        parent::__construct([]);

        $this->initializeValidation();
        $this->fill($attributes);
    }

    /**
     * @param array<string,mixed>|Arrayable $attributes
     */
    public function fill(array|Arrayable $attributes): static {
        $attributes = $attributes instanceof Arrayable
            ? $attributes->toArray()
            : $attributes;

        foreach ($attributes as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    public function get(mixed $key, mixed $default = null): mixed {
        $method = 'get' . Str::studly($key) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return parent::get($key, $default);
    }

    public function offsetSet(mixed $key, mixed $value): void {
        $method = 'set' . Str::studly($key) . 'Attribute';

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            parent::offsetSet($key, $value);
        }
    }

    public function toModel(): Model|FluentModel {
        return $this;
    }

    protected function validateAutosavedRelations(): void {
    }

    /**
     * @return array<string>
     */
    protected function validationRulesToIgnoreForParentRelations(): array {
        return [];
    }
}
