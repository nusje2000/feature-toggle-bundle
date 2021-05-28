<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Cache;

use Nusje2000\FeatureToggleBundle\Cache\AdapterInvalidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AdapterInvalidatorTest extends TestCase
{
    public function testInvalidate(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('clear')->with('nusje2000_feature_toggle');

        $invalidator = new AdapterInvalidator($adapter);
        $invalidator->invalidate();
    }
}
