<?php

declare(strict_types=1);

namespace Unit\Http\Response;

use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Http\Response\EnvironmentMapper;
use PHPUnit\Framework\TestCase;

final class EnvironmentMapperTest extends TestCase
{
    public function testMap(): void
    {
        self::assertEquals(
            new SimpleEnvironment(
                'environment-name',
                ['0.0.0.0', 'domain', 'www.host.com'],
                [
                    new SimpleFeature('feature_1', State::ENABLED),
                    new SimpleFeature('feature_2', State::DISABLED),
                ]
            ),
            EnvironmentMapper::map([
                'name' => 'environment-name',
                'hosts' => ['0.0.0.0', 'domain', 'www.host.com'],
                'features' => [
                    ['name' => 'feature_1', 'enabled' => true],
                    ['name' => 'feature_2', 'enabled' => false],
                ],
            ])
        );
    }

    public function testMapWithMissingName(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('name', 'string', null));
        EnvironmentMapper::map([
            'hosts' => ['0.0.0.0', 'domain', 'www.host.com'],
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
                ['name' => 'feature_2', 'enabled' => false],
            ],
        ]);
    }

    public function testMapWithInvalidName(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('name', 'string', 1));
        EnvironmentMapper::map([
            'name' => 1,
            'hosts' => ['0.0.0.0', 'domain', 'www.host.com'],
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
                ['name' => 'feature_2', 'enabled' => false],
            ],
        ]);
    }

    public function testMapWithMissingHosts(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('hosts', 'array', null));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
                ['name' => 'feature_2', 'enabled' => false],
            ],
        ]);
    }

    public function testMapWithNonArrayHosts(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('hosts', 'array', '0.0.0.0'));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'hosts' => '0.0.0.0',
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
                ['name' => 'feature_2', 'enabled' => false],
            ],
        ]);
    }

    public function testMapWithInvalidHost(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('hosts.0', 'string', 1));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'hosts' => [1],
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
                ['name' => 'feature_2', 'enabled' => false],
            ],
        ]);
    }

    public function testMapWithMissingFeatures(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('features', 'array', null));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'hosts' => ['host'],
        ]);
    }

    public function testMapWithNonArrayFeatures(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('features', 'array', 'features'));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'hosts' => ['host'],
            'features' => 'features',
        ]);
    }

    public function testMapWithInvalidFeature(): void
    {
        $this->expectExceptionObject(InvalidResponse::invalidKeyType('features.0', 'array', 1));
        EnvironmentMapper::map([
            'name' => 'environment-name',
            'hosts' => ['host'],
            'features' => [1],
        ]);
    }
}
