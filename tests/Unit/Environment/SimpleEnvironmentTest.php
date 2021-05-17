<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Environment;

use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeatureException;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use PHPUnit\Framework\TestCase;

final class SimpleEnvironmentTest extends TestCase
{
    public function testEmpty(): void
    {
        $environment = SimpleEnvironment::empty('environment-name');
        self::assertSame('environment-name', $environment->name());
        self::assertSame([], $environment->hosts());
        self::assertSame([], $environment->features());
    }

    public function testName(): void
    {
        $environment = new SimpleEnvironment('environment-name', [], []);
        self::assertSame('environment-name', $environment->name());
    }

    public function testHosts(): void
    {
        $environment = new SimpleEnvironment('environment-name', ['host-1', 'host-2', 'host-3'], []);

        self::assertEquals(['host-1', 'host-2', 'host-3'], $environment->hosts());
        $environment->addHost('host-4');
        self::assertEquals(['host-1', 'host-2', 'host-3', 'host-4'], $environment->hosts());
        $environment->removeHost('host-4');
        self::assertEquals(['host-1', 'host-2', 'host-3'], $environment->hosts());
    }

    public function testFeatures(): void
    {
        $environment = new SimpleEnvironment('environment-name', [], [
            $this->createFeature('feature-1'),
            $this->createFeature('feature-2'),
            $this->createFeature('feature-3'),
        ]);

        self::assertEquals([
            'feature-1' => $this->createFeature('feature-1'),
            'feature-2' => $this->createFeature('feature-2'),
            'feature-3' => $this->createFeature('feature-3'),
        ], $environment->features());

        $environment->addFeature($this->createFeature('feature-4'));

        self::assertEquals([
            'feature-1' => $this->createFeature('feature-1'),
            'feature-2' => $this->createFeature('feature-2'),
            'feature-3' => $this->createFeature('feature-3'),
            'feature-4' => $this->createFeature('feature-4'),
        ], $environment->features());

        $environment->removeFeature($this->createFeature('feature-4'));

        self::assertEquals([
            'feature-1' => $this->createFeature('feature-1'),
            'feature-2' => $this->createFeature('feature-2'),
            'feature-3' => $this->createFeature('feature-3'),
        ], $environment->features());

        $this->expectExceptionObject(DuplicateFeatureException::inEnvironment('environment-name', 'feature-1'));
        new SimpleEnvironment('environment-name', [], [
            $this->createFeature('feature-1'),
            $this->createFeature('feature-1'),
        ]);
    }

    private function createFeature(string $name): Feature
    {
        $feature = $this->createStub(Feature::class);
        $feature->method('name')->willReturn($name);

        return $feature;
    }
}
