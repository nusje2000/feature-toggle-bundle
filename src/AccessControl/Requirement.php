<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Nusje2000\FeatureToggleBundle\Feature\State;

final class Requirement
{
    /**
     * @var string
     */
    private $feature;

    /**
     * @var State
     */
    private $state;

    public function __construct(string $feature, State $state)
    {
        $this->feature = $feature;
        $this->state = $state;
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
