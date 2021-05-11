<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
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
        $features = $targetEnvironment->features();
        $features[$feature->name()] = $feature;
        $newEnvironment = new SimpleEnvironment($targetEnvironment->name(), array_values($features));

        $this->environmentRepository->persist($newEnvironment);
    }

    private function getEnvironment(string $environment): Environment
    {
        return $this->environmentRepository->find($environment);
    }
}
