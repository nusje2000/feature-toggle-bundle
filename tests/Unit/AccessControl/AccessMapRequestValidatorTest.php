<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\AccessControl;

use Nusje2000\FeatureToggleBundle\AccessControl\AccessMap;
use Nusje2000\FeatureToggleBundle\AccessControl\AccessMapRequestValidator;
use Nusje2000\FeatureToggleBundle\AccessControl\Pattern;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Exception\AccessControl\UnmetRequirement;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\FeatureToggle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AccessMapRequestValidatorTest extends TestCase
{
    public function testValidateWithoutRequirement(): void
    {
        $toggle = $this->createMock(FeatureToggle::class);
        $toggle->expects(self::never())->method('get');

        $map = $this->createMap([]);

        $validator = new AccessMapRequestValidator($map, $toggle);
        $validator->validate(new Request());
    }

    public function testValidateWithMetRequirement(): void
    {
        $toggle = $this->createMock(FeatureToggle::class);
        $toggle->expects(self::exactly(2))->method('get')->withConsecutive(['feature_1'], ['feature_2'])->willReturnOnConsecutiveCalls(
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED())
        );

        $map = $this->createMap([
            new Requirement('feature_1', State::ENABLED()),
            new Requirement('feature_2', State::DISABLED()),
        ]);

        $validator = new AccessMapRequestValidator($map, $toggle);
        $validator->validate(new Request());
    }

    public function testValidateWithUnmetRequirement(): void
    {
        $toggle = $this->createMock(FeatureToggle::class);
        $toggle->expects(self::exactly(2))->method('get')->withConsecutive(['feature_1'], ['feature_2'])->willReturnOnConsecutiveCalls(
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::ENABLED())
        );

        $map = $this->createMap([
            new Requirement('feature_1', State::ENABLED()),
            new Requirement('feature_2', State::DISABLED()),
        ]);

        $validator = new AccessMapRequestValidator($map, $toggle);
        $this->expectExceptionObject(
            UnmetRequirement::byFeature(new SimpleFeature('feature_2', State::ENABLED()), new Requirement('feature_2', State::DISABLED()))
        );
        $validator->validate(new Request());
    }

    /**
     * @param list<Requirement> $requirements
     */
    private function createMap(array $requirements): AccessMap
    {
        $map = new AccessMap();
        $map->add($this->createPattern(true, $requirements));

        return $map;
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
