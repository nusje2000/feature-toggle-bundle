<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Feature;

final class SimpleFeature implements Feature
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var State
     */
    private $state;

    public function __construct(string $name, State $state)
    {
        $this->name = $name;
        $this->state = $state;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function state(): State
    {
        return $this->state;
    }
}
