<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

interface FeatureRepository
{
    /**
     * Returns an associative array, where the key is the name of the feature
     *
     * @return array<string, Feature>
     *
     * @throws UndefinedEnvironment
     */
    public function all(string $environment): array;

    /**
     * @throws UndefinedEnvironment
     * @throws UndefinedFeature
     */
    public function find(string $environment, string $feature): Feature;

    /**
     * @throws UndefinedEnvironment
     */
    public function exists(string $environment, string $feature): bool;

    /**
     * @throws UndefinedEnvironment
     * @throws DuplicateFeature
     */
    public function add(string $environment, Feature $feature): void;

    /**
     * @throws UndefinedEnvironment
     * @throws UndefinedFeature
     */
    public function update(string $environment, Feature $feature): void;

    /**
     * @throws UndefinedEnvironment
     * @throws UndefinedFeature
     */
    public function remove(string $environment, Feature $feature): void;
}
