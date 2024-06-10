<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;

final class ArrayEnvironmentRepository implements EnvironmentRepository
{
    /**
     * @var array<string, Environment>
     */
    private array $environments = [];

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

    public function add(Environment $environment): void
    {
        if ($this->exists($environment->name())) {
            throw DuplicateEnvironment::create($environment->name());
        }

        $this->environments[$environment->name()] = $environment;
    }
}
