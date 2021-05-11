<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Environment;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeatureException;
use Nusje2000\FeatureToggleBundle\Feature\Feature;

final class SimpleEnvironment implements Environment
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array<string, Feature>
     */
    private $features = [];

    /**
     * @param list<Feature> $features
     */
    public function __construct(string $name, array $features)
    {
        $this->name = $name;

        foreach ($features as $feature) {
            $featureName = $feature->name();

            if (isset($this->features[$featureName])) {
                throw DuplicateFeatureException::inEnvironment($this->name(), $featureName);
            }

            $this->features[$featureName] = $feature;
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, Feature>
     */
    public function features(): array
    {
        return $this->features;
    }
}
