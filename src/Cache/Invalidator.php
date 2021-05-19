<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

interface Invalidator
{
    public function invalidate(): void;
}
