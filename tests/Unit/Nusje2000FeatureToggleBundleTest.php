<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit;

use Nusje2000\FeatureToggleBundle\DependencyInjection\Nusje2000FeatureToggleExtension;
use Nusje2000\FeatureToggleBundle\Nusje2000FeatureToggleBundle;
use PHPUnit\Framework\TestCase;

final class Nusje2000FeatureToggleBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new Nusje2000FeatureToggleBundle();
        $extension = $bundle->getContainerExtension();
        self::assertInstanceOf(Nusje2000FeatureToggleExtension::class, $extension);
    }
}
