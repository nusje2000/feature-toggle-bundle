<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception\Http;

use Nusje2000\FeatureToggleBundle\Exception\Throwable;
use UnexpectedValueException;

final class InvalidResponse extends UnexpectedValueException implements Throwable
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param list<int> $expected
     */
    public static function unexpectedStatus(int $status, array $expected, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Invalid response status %d, expected one of [%s].',
                $status,
                implode(',', $expected)
            ),
            $previous
        );
    }

    public static function invalidKeyType(string $key, string $expected, mixed $value, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Expected key "%s" to contain a value of type %s, but found %s.',
                $key,
                $expected,
                get_debug_type($value)
            ),
            $previous
        );
    }
}
