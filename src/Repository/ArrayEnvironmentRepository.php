<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;

final class ArrayEnvironmentRepository implements EnvironmentRepository
{
    /**
     * @var array<string, Environment>
     */
    private $environments = [];

    /**
     * @param list<Environment> $environments
     */
    public function __construct(array $environments)
    {
        foreach ($environments as $environment) {
            $this->environments[$environment->name()] = $environment;
        }
    }

    public function all(): array
    {
        return array_values($this->environments);
    }

    public function find(string $environment): Environment
    {
        if (isset($this->environments[$environment])) {
            return $this->environments[$environment];
        }

        throw UndefinedEnvironment::create($environment);
    }

    public function exists(string $environment): bool
    {
        return isset($this->environments[$environment]);
    }

    public function persist(Environment $environment): void
    {
        $this->environments[$environment->name()] = $environment;
    }
}
