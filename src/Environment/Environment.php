<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Environment;

use Nusje2000\FeatureToggleBundle\Feature\Feature;

interface Environment
{
    public function name(): string;

    /**
     * @return array<string, Feature>
     */
    public function features(): array;
}
