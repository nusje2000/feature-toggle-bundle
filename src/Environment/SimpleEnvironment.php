<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Environment;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

final class SimpleEnvironment implements Environment
{
    /**
     * @var array<string, Feature>
     */
    private array $features = [];

    /**
     * @var list<string>
     */
    private array $hosts = [];

    /**
     * @param list<string>  $hosts
     * @param list<Feature> $features
     */
    public function __construct(
        private readonly string $name,
        array $hosts,
        array $features,
    ) {
        foreach ($hosts as $host) {
            $this->addHost($host);
        }

        foreach ($features as $feature) {
            $featureName = $feature->name();

            if (isset($this->features[$featureName])) {
                throw DuplicateFeature::inEnvironment($this->name(), $featureName);
            }

            $this->addFeature($feature);
        }
    }

    public static function empty(string $name): self
    {
        return new self($name, [], []);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function hosts(): array
    {
        return $this->hosts;
    }

    public function addHost(string $host): void
    {
        if (!in_array($host, $this->hosts, true)) {
            $this->hosts[] = $host;
        }
    }

    public function removeHost(string $host): void
    {
        $key = array_search($host, $this->hosts, true);
        if (false !== $key) {
            unset($this->hosts[$key]);
        }
    }

    public function feature(string $name): Feature
    {
        $feature = $this->features[$name] ?? null;
        if (null === $feature) {
            throw UndefinedFeature::inEnvironment($this->name(), $name);
        }

        return $feature;
    }

    /**
     * @return array<string, Feature>
     */
    public function features(): array
    {
        return $this->features;
    }

    public function addFeature(Feature $feature): void
    {
        $this->features[$feature->name()] = $feature;
    }

    public function hasFeature(Feature $feature): bool
    {
        return isset($this->features[$feature->name()]);
    }

    public function removeFeature(Feature $feature): void
    {
        unset($this->features[$feature->name()]);
    }
}
