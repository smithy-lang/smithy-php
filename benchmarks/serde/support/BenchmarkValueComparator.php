<?php

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

final class BenchmarkValueComparator
{
    public static function equivalent(mixed $expected, mixed $actual): bool
    {
        if (\is_array($expected) && \is_array($actual)) {
            if (\array_keys($expected) !== \array_keys($actual)) {
                return false;
            }
            foreach ($expected as $key => $value) {
                if (!self::equivalent($value, $actual[$key])) {
                    return false;
                }
            }

            return true;
        }
        if ($expected === $actual) {
            return true;
        }

        $expectedNumber = self::normalizeNumber($expected);
        $actualNumber = self::normalizeNumber($actual);

        return $expectedNumber !== null
            && $actualNumber !== null
            && $expectedNumber === $actualNumber;
    }

    private static function normalizeNumber(mixed $value): ?string
    {
        if (\is_int($value)) {
            $value = (string) $value;
        } elseif (\is_float($value)) {
            if (!\is_finite($value)) {
                return null;
            }
            $value = \json_encode(
                $value,
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            );
        } elseif (!\is_string($value)) {
            return null;
        }

        if (!\preg_match(
            '/^([+-]?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/',
            $value,
            $matches
        )) {
            return null;
        }

        $fraction = $matches[3] ?? '';
        $digits = \ltrim($matches[2] . $fraction, '0');
        if ($digits === '') {
            return '0';
        }

        $exponent = self::parseExponent($matches[4] ?? '0');
        if ($exponent === null) {
            return null;
        }

        $coefficient = \rtrim($digits, '0');
        $trailingZeros = \strlen($digits) - \strlen($coefficient);
        $adjustment = $trailingZeros - \strlen($fraction);
        if (($adjustment > 0 && $exponent > \PHP_INT_MAX - $adjustment)
            || ($adjustment < 0 && $exponent < \PHP_INT_MIN - $adjustment)
        ) {
            return null;
        }

        $sign = $matches[1] === '-' ? '-' : '';

        return $sign . $coefficient . 'e' . ($exponent + $adjustment);
    }

    private static function parseExponent(string $exponent): ?int
    {
        $sign = '';
        if ($exponent[0] === '+' || $exponent[0] === '-') {
            $sign = $exponent[0] === '-' ? '-' : '';
            $exponent = \substr($exponent, 1);
        }
        $exponent = \ltrim($exponent, '0');
        $normalized = $exponent === '' ? '0' : $sign . $exponent;
        $value = (int) $normalized;

        return (string) $value === $normalized ? $value : null;
    }
}
