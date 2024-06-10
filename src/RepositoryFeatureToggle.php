<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle;

use Nusje2000\FeatureToggleBundle\Exception\DisabledFeature;
use Nusje2000\FeatureToggleBundle\Exception\EnabledFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;

final class RepositoryFeatureToggle implements FeatureToggle
{
    public function __construct(
        private readonly FeatureRepository $featureRepository,
        private readonly string $environmentName,
    ) {
    }

    public function get(string $feature): Feature
    {
        return $this->featureRepository->find($this->environmentName, $feature);
    }

    public function exists(string $feature): bool
    {
        return $this->featureRepository->exists($this->environmentName, $feature);
    }

    public function isEnabled(string $feature): bool
    {
        return $this->get($feature)->state()->isEnabled();
    }

    public function isDisabled(string $feature): bool
    {
        return $this->get($feature)->state()->isDisabled();
    }

    public function assertDefined(string $feature): void
    {
        if (!$this->exists($feature)) {
            throw UndefinedFeature::inEnvironment($this->environmentName, $feature);
        }
    }

    public function assertEnabled(string $feature): void
    {
        if (!$this->isEnabled($feature)) {
            throw DisabledFeature::inEnvironment($this->environmentName, $feature);
        }
    }

    public function assertDisabled(string $feature): void
    {
        if (!$this->isDisabled($feature)) {
            throw EnabledFeature::inEnvironment($this->environmentName, $feature);
        }
    }
}
