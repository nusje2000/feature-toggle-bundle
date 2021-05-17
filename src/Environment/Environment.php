<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Environment;

use Nusje2000\FeatureToggleBundle\Feature\Feature;

interface Environment
{
    public function name(): string;

    /**
     * @return list<string>
     */
    public function hosts(): array;

    public function addHost(string $host): void;

    public function removeHost(string $host): void;

    /**
     * @return array<string, Feature>
     */
    public function features(): array;

    public function addFeature(Feature $feature): void;

    public function removeFeature(Feature $feature): void;
}
