<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Client;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Controller\Client\InvalidateCacheController;
use PHPUnit\Framework\TestCase;

final class InvalidateCacheControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::once())->method('invalidate');

        $controller = new InvalidateCacheController($invalidator);
        $controller();
    }
}
