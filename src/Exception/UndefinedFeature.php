<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception;

use UnexpectedValueException;

final class UndefinedFeature extends UnexpectedValueException implements Throwable
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    static function inEnvironment(string $environment, string $feature, ?Throwable $previous = null): self
    {
        return new self(sprintf('Could not find feature "%s" within environment "%s".', $feature, $environment), $previous);
    }
}
