<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Feature;

use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use PHPUnit\Framework\TestCase;

final class SimpleFeatureTest extends TestCase
{
    public function testName(): void
    {
        $feature = new SimpleFeature('name', State::ENABLED);
        self::assertSame('name', $feature->name());
    }

    public function testState(): void
    {
        $feature = new SimpleFeature('name', State::ENABLED);
        self::assertEquals(State::ENABLED, $feature->state());

        $feature = new SimpleFeature('name', State::DISABLED);
        self::assertEquals(State::DISABLED, $feature->state());
    }

    public function testEnable(): void
    {
        $feature = new SimpleFeature('name', State::DISABLED);
        $feature->enable();
        self::assertEquals(State::ENABLED, $feature->state());
    }

    public function testDisable(): void
    {
        $feature = new SimpleFeature('name', State::ENABLED);
        $feature->disable();
        self::assertEquals(State::DISABLED, $feature->state());
    }
}
