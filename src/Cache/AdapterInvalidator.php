<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AdapterInvalidator implements Invalidator
{
    private const CACHE_PREFIX = 'nusje2000_feature_toggle';

    /**
     * @var AdapterInterface
     */
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function invalidate(): void
    {
        $this->adapter->clear(self::CACHE_PREFIX);
    }
}
