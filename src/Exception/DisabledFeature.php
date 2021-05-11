<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception;

use LogicException;

final class DisabledFeature extends LogicException implements Throwable
{
    private function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    static function inEnvironment(string $environment, string $feature, Throwable $previous = null): self
    {
        return new self(sprintf('Feature "%s" is disabled (environment: "%s")', $feature, $environment), $previous);
    }
}
