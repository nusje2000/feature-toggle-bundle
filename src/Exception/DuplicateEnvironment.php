<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception;

use UnexpectedValueException;

final class DuplicateEnvironment extends UnexpectedValueException implements Throwable
{
    private function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    static function create(string $environment, Throwable $previous = null): self
    {
        return new self(sprintf('Environment "%s" already exists.', $environment), $previous);
    }
}
