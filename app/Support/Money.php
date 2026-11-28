<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Cash amounts as exact two-place decimal strings. Amounts are stored as decimal(15,2) and read through
 * `decimal:2` casts, so doing the arithmetic in bcmath keeps every balance exact to the cent, where floats
 * drift (0.10 + 0.20 is not 0.30 as a float).
 */
class Money
{
    public const string ZERO = '0.00';

    /**
     * A numeric value (a `decimal:2` attribute, a database aggregate, validated input) as a two-place
     * decimal string. Anything that isn't numeric, such as a null attribute, counts as zero.
     *
     * @param mixed $value
     *
     * @return numeric-string
     */
    public static function of(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            $value = number_format($value, 2, '.', '');
        }

        if (! is_string($value) || ! is_numeric($value)) {
            return self::ZERO;
        }

        return bcadd($value, '0', 2);
    }

    /**
     * @param iterable<mixed> $amounts
     *
     * @return numeric-string
     */
    public static function sum(iterable $amounts): string
    {
        $total = self::ZERO;

        foreach ($amounts as $amount) {
            $total = bcadd($total, self::of($amount), 2);
        }

        return $total;
    }
}
