<?php

declare(strict_types=1);

namespace Unit\Http\Response;

use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Http\Response\FeatureMapper;
use PHPUnit\Framework\TestCase;

final class FeatureMapperTest extends TestCase
{
    public function testMap(): void
    {
        self::assertEquals(
            new SimpleFeature('feature_1', State::ENABLED),
            FeatureMapper::map(['name' => 'feature_1', 'enabled' => true])
        );
        self::assertEquals(
            new SimpleFeature('feature_1', State::DISABLED),
            FeatureMapper::map(['name' => 'feature_1', 'enabled' => false])
        );
        self::assertEquals(
            new SimpleFeature('feature_1', State::ENABLED, 'fooBar'),
            FeatureMapper::map(['name' => 'feature_1', 'enabled' => true, 'description' => 'fooBar'])
        );
    }

    public function testMapWithMissingName(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('name', 'string', null));
        FeatureMapper::map(['enabled' => true]);
    }

    public function testMapWithInvalidName(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('name', 'string', 1));
        FeatureMapper::map(['name' => 1, 'enabled' => true]);
    }

    public function testMapWithMissingState(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('enabled', 'bool', null));
        FeatureMapper::map(['name' => 'feature_1']);
    }

    public function testMapWithInvalidState(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('enabled', 'bool', 1));
        FeatureMapper::map(['name' => 'feature_1', 'enabled' => 1]);
    }

    public function testMapWithInvalidDescription(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('description', 'string|null', []));
        FeatureMapper::map(['name' => 'feature_1', 'enabled' => true, 'description' => []]);
    }
}
