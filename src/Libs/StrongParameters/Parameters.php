<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs\StrongParameters;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use JsonSerializable;
use RuntimeException;
use SilvertipSoftware\LaravelSupport\Libs\ArrUtils;

class Parameters implements Arrayable, ArrayAccess, Jsonable, JsonSerializable {

    /** @var array<array<string,mixed>|Parameters> */
    protected array $convertedArrays = [];

    /** @var array<string,mixed> */
    protected array $params;
    protected bool $permitted = false;

    /**
     * @param array<string,mixed> $params
     */
    final public function __construct(array $params = []) {
        $this->params = $params;
    }

    public function eachKey(Closure $callback): void {
        foreach (array_keys($this->params) as $key) {
            $callback($key);
        }
    }

    public function exists(string $key): bool {
        return Arr::exists($this->params, $key);
    }

    public function fetch(string $key, mixed ...$args): mixed {
        if (Arr::has($this->params, $key)) {
            $value = Arr::get($this->params, $key);
        } elseif (count($args) > 0) {
            $value = value($args[0]);
        } else {
            throw new ParameterMissingException($key);
        }

        return $this->convertValueToParameters($value);
    }

    public function has(string $key): bool {
        return Arr::has($this->params, $key);
    }

    public function hasValue(mixed $value): bool {
        return in_array($value, $this->params);
    }

    public function isEmpty(): bool {
        return empty($this->params);
    }

    public function isPermitted(): bool {
        return $this->permitted;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array {
        return array_map(function ($value) {
            return $value instanceof Parameters
                ? $value->jsonSerialize()
                : $value;
        }, $this->params);
    }

    /**
     * @return array<string>
     */
    public function keys(): array {
        return array_keys($this->params);
    }

    public function offsetExists(mixed $offset): bool {
        return array_key_exists($offset, $this->params);
    }

    public function offsetGet(mixed $offset): mixed {
        if (!array_key_exists($offset, $this->params)) {
            return null;
        }

        return $this->convertHashesToParameters($offset, $this->params[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->params[$offset] = $value;
    }

    public function __get(mixed $offset): mixed {
        return $this->offsetGet($offset);
    }

    public function __set(mixed $offset, mixed $value) {
        $this->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->params[$offset]);
    }

    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    protected function flattenNonAssoc(array $array): array {
        $ret = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_string($k)) {
                        $ret[] = [$k => $v];
                    } else {
                        $ret[] = $v;
                    }
                }
            } else {
                $ret[] = $value;
            }
        }

        return $ret;
    }

    public function permit(mixed ...$filterArgs): static {
        $ret = new static();

        foreach ($this->flattenNonAssoc($filterArgs) as $key => $filter) {
            if (is_string($filter)) {
                $this->permittedScalarFilter($ret, $filter);
            } elseif (is_array($filter) && Arr::isAssoc($filter)) {
                $this->hashFilter($ret, $filter);
            } else {
                throw new RuntimeException('Bad filter type ' . gettype($filter));
            }
        }

        $ret->setPermitted();

        return $ret;
    }

    /**
     * @param string|array<string|int,mixed> $key
     */
    public function require(string|array $key): mixed {
        if (is_array($key)) {
            return array_map(function ($k) {
                return $this->require($k);
            }, $key);
        }

        $value = $this[$key];

        if ($value === [] || $value === null) {
            throw new ParameterMissingException($key);
        }

        return $value;
    }

    public function setPermitted(): static {
        $this->eachPair(function ($key, $value) {
            $elems = is_array($value)
                ? (Arr::isAssoc($value) ? array_values($value) : $value)
                : [$value];

            foreach ($elems as $v) {
                if ($v instanceof Parameters) {
                    $v->setPermitted();
                }
            }
        });

        $this->permitted = true;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array {
        if ($this->isPermitted()) {
            return $this->convertParametersToHashes($this->params);
        }

        throw new UnfilteredParametersException();
    }

    public function toJson(mixed $options = 0): string {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * @return mixed[]
     */
    public function values(): array {
        return array_values($this->params);
    }

    /**
     * @param array<string|int,mixed>|Parameters $object
     * @param Closure(Parameters):Parameters $callback
     * @return Parameters|Parameters[]
     */
    protected static function eachElement(
        array|Parameters $object,
        mixed $filter,
        Closure $callback
    ): array|Parameters {
        if (is_array($object)) {
            $parameterObjects = array_filter($object, function ($elem) {
                return ($elem instanceof Parameters);
            });

            return array_map($callback, $parameterObjects);
        } elseif ($object instanceof Parameters) {
            if ($object->hasNestedAttributes()) {
                return $object->eachNestedAttribute($callback);
            } else {
                return $callback($object);
            }
        }
    }

    protected static function isNestedAttribute(string|int $key, mixed $value): bool {
        return is_numeric($key)
            && is_int(0 + $key)
            && ((is_array($value) && Arr::isAssoc($value)) || $value instanceof Parameters);
    }

    // @phpstan-assert-if-true string|int|float|bool|UploadedFile|null $value
    protected static function isPermittedScalar(mixed $value): bool {
        return $value === null
            || is_string($value)
            || is_numeric($value)
            || is_bool($value)
            || ($value instanceof UploadedFile);
    }

    /**
     * @param array<mixed> $arr
     * @return array<string|int|float|bool|UploadedFile|static>
     */
    protected static function permitAnyInArray($arr): array {
        $ret = [];

        foreach ($arr as $element) {
            if (Parameters::isPermittedScalar($element)) {
                $ret[] = $element;
            } elseif ($element instanceof Parameters) {
                $ret[] = $element->permitAnyInSelf();
            }
        }

        return $ret;
    }

    /**
     * @return ?array<string|int|float|bool|UploadedFile>
     */
    protected function arrayOfPermittedScalars(mixed $value): ?array {
        if (is_array($value)) {
            $allScalars = true;
            foreach ($value as $v) {
                if (!Parameters::isPermittedScalar($v)) {
                    $allScalars = false;
                    break;
                }
            }

            if ($allScalars) {
                return $value;
            }
        }

        return null;
    }

    protected function convertHashesToParameters(string|int $key, mixed $value): mixed {
        $converted = $this->convertValueToParameters($value);
        if ($converted !== $value) {
            $this->params[$key] = $converted;
        }

        return $converted;
    }

    protected function convertParametersToHashes(mixed $value): mixed {
        if (is_array($value)) {
            $ret = [];
            foreach ($value as $k => $v) {
                $ret[$k] = $this->convertParametersToHashes($v);
            }
            return $ret;
        } elseif ($value instanceof Parameters) {
            return $value->toArray();
        } else {
            return $value;
        }
    }

    protected function convertValueToParameters(mixed $value): mixed {
        if (is_array($value)) {
            if (!Arr::isAssoc($value)) {
                if (in_array($value, $this->convertedArrays, true)) {
                    return $value;
                }

                $converted = array_map(function ($v) {
                    return $this->convertValueToParameters($v);
                }, $value);

                $this->convertedArrays[] = $converted;
                return $converted;
            } else {
                return new static($value);
            }
        }

        return $value;
    }

    /**
     * @param Closure(Parameters):Parameters $callback
     */
    protected function eachNestedAttribute(Closure $callback): static {
        $ret = new static();

        $this->eachPair(function ($key, $value) use ($ret, $callback) {
            if (Parameters::isNestedAttribute($key, $value)) {
                $ret[$key] = $callback($value);
            }
        });

        return $ret;
    }

    protected function eachPair(Closure $callback): void {
        foreach ($this->params as $key => $value) {
            $callback($key, $this->convertHashesToParameters($key, $value));
        }
    }

    /**
     * @param array<string,mixed> $filter
     */
    protected function hashFilter(Parameters $parameters, array $filter): void {
        foreach ($filter as $key => $filterValue) {
            if (is_numeric($key)) {
                $key = $filterValue;
                $filterValue = [];
            }

            $value = $this->offsetGet($key);
            if ($filterValue === []) {
                $scalars = $this->arrayOfPermittedScalars($value);
                if ($scalars !== null) {
                    $parameters[$key] = $scalars;
                }
            } elseif ($filterValue instanceof AnyStructure) {
                if ($value instanceof Parameters) {
                    $parameters[$key] = $value->permitAnyInSelf();
                }
            } elseif ($filterValue !== null) {
                if (is_array($value) || $value instanceof Parameters) {
                    $newPermits = is_array($filterValue) && count($filterValue) == 1 && !Arr::isAssoc($filterValue)
                        ? $filterValue[0]
                        : $filterValue;

                    $mapped = Parameters::eachElement($value, $filterValue, function ($element) use ($newPermits) {
                        return $element->permit($newPermits);
                    });

                    $parameters[$key] = $mapped;
                }
            }
        }
    }

    protected function hasNestedAttributes(): bool {
        foreach ($this->params as $key => $value) {
            if (Parameters::isNestedAttribute($key, $value)) {
                return true;
            }
        }

        return false;
    }

    protected function permitAnyInSelf(): static {
        $ret = new static();

        $this->eachPair(function ($key, $value) use ($ret) {
            if (Parameters::isPermittedScalar($value)) {
                $ret[$key] = $value;
            } elseif (is_array($value)) {
                $ret[$key] = Parameters::permitAnyInArray($value);
            } elseif ($value instanceof Parameters) {
                $ret[$key] = $value->permitAnyInSelf();
            }
        });

        return $ret;
    }

    protected function permittedScalarFilter(Parameters $parameters, string $filter): void {
        if ($this->offsetExists($filter)) {
            $value = $this->params[$filter];

            if (Parameters::isPermittedScalar($value)) {
                $parameters[$filter] = $value;
            }
        }
    }
}
