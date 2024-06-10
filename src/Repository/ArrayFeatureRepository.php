<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

final class ArrayFeatureRepository implements FeatureRepository
{
    public function __construct(
        private readonly EnvironmentRepository $environmentRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(string $environment): array
    {
        $subject = $this->getEnvironment($environment);

        return $subject->features();
    }

    public function find(string $environment, string $feature): Feature
    {
        $subject = $this->all($environment)[$feature] ?? null;
        if (null === $subject) {
            throw UndefinedFeature::inEnvironment($environment, $feature);
        }

        return $subject;
    }

    public function exists(string $environment, string $feature): bool
    {
        return isset($this->all($environment)[$feature]);
    }

    public function add(string $environment, Feature $feature): void
    {
        $targetEnvironment = $this->getEnvironment($environment);
        if ($targetEnvironment->hasFeature($feature)) {
            throw DuplicateFeature::inEnvironment($environment, $feature->name());
        }

        $targetEnvironment->addFeature($feature);
    }

    public function update(string $environment, Feature $feature): void
    {
        $targetEnvironment = $this->getEnvironment($environment);
        if (!$targetEnvironment->hasFeature($feature)) {
            throw UndefinedFeature::inEnvironment($environment, $feature->name());
        }

        $targetEnvironment->addFeature($feature);
    }

    public function remove(string $environment, Feature $feature): void
    {
        $targetEnvironment = $this->getEnvironment($environment);
        if (!$targetEnvironment->hasFeature($feature)) {
            throw UndefinedFeature::inEnvironment($environment, $feature->name());
        }

        $targetEnvironment->removeFeature($feature);
    }

    private function getEnvironment(string $environment): Environment
    {
        return $this->environmentRepository->find($environment);
    }
}
