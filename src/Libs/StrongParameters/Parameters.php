<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs\StrongParameters;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class Parameters implements Arrayable, ArrayAccess {

    protected $params;
    protected $permitted = false;
    protected $convertedArrays = [];

    final public function __construct(array $params = []) {
        $this->params = $params;
    }

    public function isPermitted() {
        return $this->permitted;
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

    public function __get($offset) {
        return $this->offsetGet($offset);
    }

    public function __set($offset, $value) {
        $this->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->params[$offset]);
    }

    public function permit($filters) {
        $filters = (array) $filters;
        $ret = new static();

        foreach ($filters as $key => $filter) {
            if (!is_numeric($key)) {
                $filter = [
                    $key => $filter
                ];
            }

            if (is_string($filter)) {
                $this->permittedScalarFilter($ret, $filter);
            } elseif (is_array($filter) && Arr::isAssoc($filter)) {
                $this->hashFilter($ret, $filter);
            }
        }

        $ret->setPermitted();

        return $ret;
    }

    public function require($key) {
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

    public function setPermitted() {
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

    public function toArray() {
        if ($this->isPermitted()) {
            return $this->convertParametersToHashes($this->params);
        }

        throw new UnfilteredParametersException();
    }

    protected static function eachElement($object, $filter, $callback) {
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

    protected static function isNestedAttribute($key, $value) {
        return is_numeric($key)
            && is_int(0 + $key)
            && ((is_array($value) && Arr::isAssoc($value)) || $value instanceof Parameters);
    }

    protected static function isPermittedScalar($value) {
        return is_string($value)
            || is_numeric($value)
            || is_bool($value)
            || ($value instanceof UploadedFile);
    }

    protected static function permitAnyInArray($arr) {
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

    protected function arrayOfPermittedScalars($value) {
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

    protected function convertHashesToParameters($key, $value) {
        $converted = $this->convertValueToParameters($value);
        if ($converted !== $value) {
            $this->params[$key] = $converted;
        }

        return $converted;
    }

    protected function convertParametersToHashes($value) {
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

    protected function convertValueToParameters($value) {
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

    protected function eachNestedAttribute($callback) {
        $ret = new static();

        $this->eachPair(function ($key, $value) use ($ret, $callback) {
            if (Parameters::isNestedAttribute($key, $value)) {
                $ret[$key] = $callback($value);
            }
        });

        return $ret;
    }

    protected function eachPair(callable $callback) {
        foreach ($this->params as $key => $value) {
            $callback($key, $this->convertHashesToParameters($key, $value));
        }
    }

    protected function hashFilter($parameters, $filter) {
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

    protected function hasNestedAttributes() {
        foreach ($this->params as $key => $value) {
            if (Parameters::isNestedAttribute($key, $value)) {
                return true;
            }
        }

        return false;
    }

    protected function permitAnyInSelf() {
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

    protected function permittedScalarFilter($parameters, $filter) {
        if ($this->offsetExists($filter)) {
            $value = $this->params[$filter];

            if (Parameters::isPermittedScalar($value)) {
                $parameters[$filter] = $value;
            }
        }
    }
}
