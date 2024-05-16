<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Feature;

use Nusje2000\FeatureToggleBundle\Feature\State;
use PHPUnit\Framework\TestCase;

final class StateTest extends TestCase
{
    public function testFromBoolean(): void
    {
        self::assertTrue(State::fromBoolean(true)->isEnabled());
        self::assertFalse(State::fromBoolean(false)->isEnabled());
    }

    public function testIsEnabled(): void
    {
        self::assertTrue(State::ENABLED->isEnabled());
        self::assertFalse(State::DISABLED->isEnabled());
    }

    public function testIsDisabled(): void
    {
        self::assertFalse(State::ENABLED->isDisabled());
        self::assertTrue(State::DISABLED->isDisabled());
    }
}
