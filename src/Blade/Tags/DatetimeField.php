<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade\Tags;

use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use RuntimeException;

class DatetimeField extends TextField {

    public function render(): HtmlString {
        $options = $this->options;
        $options['value'] = Arr::has($options, 'value')
            ? $this->stringifyValue(Arr::get($options, 'value'))
            : $this->formatDate($this->value());

        $options['min'] = $this->formatDate($this->datetimeValue(Arr::get($options, 'min')));
        $options['max'] = $this->formatDate($this->datetimeValue(Arr::get($options, 'max')));
        $this->options = $options;

        return parent::render();
    }

    protected function dateFormat(): string {
        throw new RuntimeException('implement in subclass');
    }

    protected function datetimeValue(mixed $value): mixed {
        if (is_string($value)) {
            try {
                $value = Carbon::parse($value);
            } catch (Exception $ex) {
                $value = null;
            }
        } elseif (is_int($value)) {
            $value = Carbon::createFromTimestamp($value);
        }

        return $value;
    }

    protected function formatDate(mixed $value): ?string {
        if (!$value) {
            return null;
        }

        try {
            return (new Carbon($value))->format($this->dateFormat());
        } catch (Exception $ex) {
            return null;
        }
    }

    protected function stringifyValue(mixed $value): string {
        return is_string($value)
            ? $value
            : $this->formatDate($value);
    }
}
