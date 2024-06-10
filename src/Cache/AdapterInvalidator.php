<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AdapterInvalidator implements Invalidator
{
    private const CACHE_PREFIX = 'nusje2000_feature_toggle';

    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {
    }

    public function invalidate(): void
    {
        $this->adapter->clear(self::CACHE_PREFIX);
    }
}
