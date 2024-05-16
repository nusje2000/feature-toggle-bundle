<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception\AccessControl;

use LogicException;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Exception\Throwable;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

final class UnmetRequirement extends LogicException implements Throwable
{
    private function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function byFeature(Feature $feature, Requirement $requirement, ?Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Feature "%s" has state "%s" (requires %s)',
                $feature->name(),
                $feature->state()->name,
                $requirement->state()->name,
            ),
            $previous
        );
    }
}
