<?php

declare(strict_types=1);

namespace SilvertipSoftware\LaravelSupport\Blade;

class Utils {

    const TAG_NAME_START_REGEXP_SET = "@:A-Z_a-z\u{C0}-\u{D6}\u{D8}-\u{F6}\u{F8}-\u{2FF}\u{370}-\u{37D}\u{37F}-"
        . "\u{1FFF}\u{200C}-\u{200D}\u{2070}-\u{218F}\u{2C00}-\u{2FEF}\u{3001}-\u{D7FF}\u{F900}-\u{FDCF}"
        . "\u{FDF0}-\u{FFFD}\u{D800}\u{DC00}-\u{DB7F}\u{DFFF}";
    const TAG_NAME_START_REGEXP = '/[^SUB]/';
    const TAG_NAME_FOLLOWING_REGEXP = "/[^SUB\-.0-9\x{B7}\u{0300}-\u{036F}\u{203F}-\u{2040}]/";
    const TAG_NAME_REPLACEMENT_CHAR = '_';

    public static function xmlNameEscape(?string $name): string {
        if (empty($name)) {
            return '';
        }

        $startingChar = preg_replace(
            str_replace('SUB', self::TAG_NAME_START_REGEXP_SET, self::TAG_NAME_START_REGEXP),
            self::TAG_NAME_REPLACEMENT_CHAR,
            $name[0]
        );

        if (strlen($name) == 1) {
            return $startingChar;
        }

        $followingCharacters = preg_replace(
            str_replace('SUB', self::TAG_NAME_START_REGEXP_SET, self::TAG_NAME_FOLLOWING_REGEXP),
            self::TAG_NAME_REPLACEMENT_CHAR,
            substr($name, 1)
        );

        return $startingChar . $followingCharacters;
    }

    /**
     * @return array<mixed>
     */
    public static function determineTagArgs(mixed ...$args): array {
        $lastIx = count($args) - 1;

        // ensure first is not an array
        if ($lastIx >= 0 && is_array($args[0])) {
            for ($ix = $lastIx; $ix > 0; $ix--) {
                $args[$ix] = $args[$ix - 1];
            }
            $args[0] = null;
        }

        // move callback to end of array
        for ($ix = 0; $ix <= $lastIx; $ix++) {
            $arg = $args[$ix];

            if (!is_string($arg) && is_callable($arg)) {
                $args[$lastIx] = $arg;
                break;
            }
        }

        for ($otherIx = $ix; $otherIx < $lastIx; $otherIx++) {
            $args[$otherIx] = null;
        }

        return $args;
    }
}
