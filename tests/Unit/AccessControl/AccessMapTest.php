<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\AccessControl;

use Nusje2000\FeatureToggleBundle\AccessControl\AccessMap;
use Nusje2000\FeatureToggleBundle\AccessControl\Pattern;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Feature\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AccessMapTest extends TestCase
{
    public function testRequirements(): void
    {
        $request = new Request();

        $requirements = [
            new Requirement('feature_1', State::ENABLED),
            new Requirement('feature_2', State::DISABLED),
        ];

        $pattern = $this->createPattern(true, $requirements);

        $map = new AccessMap();
        $map->add($this->createPattern(false, [new Requirement('feature_1', State::ENABLED)]));
        $map->add($this->createPattern(false, [new Requirement('feature_2', State::ENABLED)]));
        $map->add($this->createPattern(false, [new Requirement('feature_3', State::ENABLED)]));

        self::assertEmpty($map->requirements($request));

        $map->add($pattern);
        self::assertSame($requirements, $map->requirements($request));

        $map->add($this->createPattern(true, [new Requirement('feature_1', State::ENABLED)]));
        self::assertSame($requirements, $map->requirements($request));
    }

    /**
     * @param list<Requirement> $requirements
     */
    protected function createPattern(bool $matches, array $requirements): Pattern
    {
        $pattern = $this->createMock(Pattern::class);
        $pattern->method('matches')->willReturn($matches);
        $pattern->method('requirements')->willReturn($requirements);

        return $pattern;
    }
}
