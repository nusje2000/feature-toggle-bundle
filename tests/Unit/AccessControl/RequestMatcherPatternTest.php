<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\AccessControl;

use Nusje2000\FeatureToggleBundle\AccessControl\RequestMatcherPattern;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Feature\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class RequestMatcherPatternTest extends TestCase
{
    public function testMatches(): void
    {
        $request = new Request();

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher->method('matches')->with($request)->willReturn(true);
        $requirement = new RequestMatcherPattern($matcher, [new Requirement('feature_1', State::ENABLED)]);
        self::assertTrue($requirement->matches($request));

        $matcher = $this->createMock(RequestMatcherInterface::class);
        $matcher->method('matches')->with($request)->willReturn(false);
        $requirement = new RequestMatcherPattern($matcher, [new Requirement('feature_1', State::ENABLED)]);
        self::assertFalse($requirement->matches($request));
    }

    public function testRequirements(): void
    {
        $matcher = $this->createStub(RequestMatcherInterface::class);
        $requirement = new RequestMatcherPattern($matcher, [new Requirement('feature_1', State::ENABLED)]);
        self::assertEquals($requirement->requirements(), [new Requirement('feature_1', State::ENABLED)]);
    }
}
