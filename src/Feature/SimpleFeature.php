<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Feature;

final class SimpleFeature implements Feature
{
    public function __construct(
        private readonly string $name,
        private State $state,
        private ?string $description = null,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function state(): State
    {
        return $this->state;
    }

    public function enable(): void
    {
        $this->state = State::ENABLED;
    }

    public function disable(): void
    {
        $this->state = State::DISABLED;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
