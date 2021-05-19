<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Cache;

use Nusje2000\FeatureToggleBundle\Cache\NullInvalidator;
use PHPUnit\Framework\TestCase;

final class NullInvalidatorTest extends TestCase
{
    public function testInvalidate(): void
    {
        $invalidator = new NullInvalidator();
        $invalidator->invalidate();

        /** @psalm-suppress InternalMethod */
        $this->addToAssertionCount(1);
    }
}
