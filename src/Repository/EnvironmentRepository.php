<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;

interface EnvironmentRepository
{
    /**
     * @return list<Environment>
     */
    public function all(): array;

    /**
     * @throws UndefinedEnvironment
     */
    public function find(string $environment): Environment;

    public function exists(string $environment): bool;

    public function persist(Environment $environment): void;
}
