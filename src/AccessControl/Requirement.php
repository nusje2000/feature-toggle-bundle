<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Nusje2000\FeatureToggleBundle\Feature\State;

final class Requirement
{
    public function __construct(
        private readonly string $feature,
        private readonly State $state,
    ) {
    }

    public function feature(): string
    {
        return $this->feature;
    }

    public function state(): State
    {
        return $this->state;
    }
}
