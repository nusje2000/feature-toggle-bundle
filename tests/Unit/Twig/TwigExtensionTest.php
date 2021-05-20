<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Twig;

use Nusje2000\FeatureToggleBundle\FeatureToggle;
use Nusje2000\FeatureToggleBundle\Twig\TwigExtension;
use PHPUnit\Framework\TestCase;

final class TwigExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $toggle = $this->createMock(FeatureToggle::class);
        $toggle->method('isEnabled')->with('feature')->willReturn(true);
        $toggle->method('isDisabled')->with('feature')->willReturn(false);

        $functions = (new TwigExtension($toggle))->getFunctions();

        self::assertCount(2, $functions);
        self::assertArrayHasKey(0, $functions);
        self::assertArrayHasKey(1, $functions);

        $function = $functions[0];
        self::assertEquals('is_feature_enabled', $function->getName());
        $callable = $function->getCallable();
        self::assertEquals([$toggle, 'isEnabled'], $callable);
        self::assertIsCallable($callable);
        self::assertTrue($callable('feature'));

        $function = $functions[1];
        self::assertEquals('is_feature_disabled', $function->getName());
        $callable = $function->getCallable();
        self::assertEquals([$toggle, 'isDisabled'], $callable);
        self::assertIsCallable($callable);
        self::assertFalse($callable('feature'));
    }
}
