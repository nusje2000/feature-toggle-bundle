<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

final class NullInvalidator implements Invalidator
{
    public function invalidate(): void
    {
    }
}
