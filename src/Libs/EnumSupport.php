<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Libs;

use BackedEnum;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SilvertipSoftware\LaravelSupport\Eloquent\Naming\Name;
use SilvertipSoftware\LaravelSupport\Eloquent\Translation;
use SilvertipSoftware\LaravelSupport\Libs\StrUtils;

/**
 * @phpstan-type OptionHash array<string,mixed>
 */
trait EnumSupport {
    use Translation;

    /**
     * @param OptionHash $options
     */
    public static function orderedCasesForOptions(array $options = [], int|Closure $orderBy = 0): Collection {
        $optionData = collect(
            array_map(fn ($case) => [
                $case->humanize($options),
                $case instanceof BackedEnum ? $case->value : $case->name,
                $case
            ], static::cases())
        );

        return $optionData->sortBy($orderBy)->map(fn ($tuple) => [$tuple[0], $tuple[1]])->values();
    }

    public static function modelName(): Name {
        return new Name(static::class, null);
    }

    /**
     * @param OptionHash $options
     */
    public function humanize(array $options = []): string {
        $options['default'] = Arr::get($options, 'default', StrUtils::humanize($this->name));

        return $this->humanAttributeName('value.' . $this->name, $options);
    }
}
