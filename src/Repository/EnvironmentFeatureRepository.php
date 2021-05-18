<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

final class EnvironmentFeatureRepository implements FeatureRepository
{
    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    public function __construct(EnvironmentRepository $environmentRepository)
    {
        $this->environmentRepository = $environmentRepository;
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

    public function persist(string $environment, Feature $feature): void
    {
        $targetEnvironment = $this->getEnvironment($environment);
        $targetEnvironment->addFeature($feature);

        $this->environmentRepository->persist($targetEnvironment);
    }

    public function remove(string $environment, Feature $feature): void
    {
        $targetEnvironment = $this->getEnvironment($environment);
        $targetEnvironment->removeFeature($feature);

        $this->environmentRepository->persist($targetEnvironment);
    }

    private function getEnvironment(string $environment): Environment
    {
        return $this->environmentRepository->find($environment);
    }
}
