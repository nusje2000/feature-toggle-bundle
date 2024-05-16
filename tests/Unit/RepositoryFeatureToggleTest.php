<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit;

use Nusje2000\FeatureToggleBundle\Exception\DisabledFeature;
use Nusje2000\FeatureToggleBundle\Exception\EnabledFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\FeatureToggle;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\RepositoryFeatureToggle;
use PHPUnit\Framework\TestCase;

final class RepositoryFeatureToggleTest extends TestCase
{
    public function testGet(): void
    {
        $toggle = $this->createFeatureToggle();

        self::assertEquals(new SimpleFeature('disabled-feature', State::DISABLED), $toggle->get('disabled-feature'));
        self::assertEquals(new SimpleFeature('enabled-feature', State::ENABLED), $toggle->get('enabled-feature'));
    }

    public function testExists(): void
    {
        $toggle = $this->createFeatureToggle();

        self::assertTrue($toggle->exists('disabled-feature'));
        self::assertTrue($toggle->exists('enabled-feature'));
        self::assertFalse($toggle->exists('undefined-feature'));
    }

    public function testIsEnabled(): void
    {
        $toggle = $this->createFeatureToggle();

        self::assertFalse($toggle->isEnabled('disabled-feature'));
        self::assertTrue($toggle->isEnabled('enabled-feature'));
    }

    public function testIsDisabled(): void
    {
        $toggle = $this->createFeatureToggle();

        self::assertTrue($toggle->isDisabled('disabled-feature'));
        self::assertFalse($toggle->isDisabled('enabled-feature'));
    }

    public function testAssertDefined(): void
    {
        $toggle = $this->createFeatureToggle();

        $toggle->assertDefined('disabled-feature');
        $toggle->assertDefined('enabled-feature');

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('existing-env', 'undefined-feature'));
        $toggle->assertDefined('undefined-feature');
    }

    public function testAssertEnabled(): void
    {
        $toggle = $this->createFeatureToggle();

        $toggle->assertEnabled('enabled-feature');

        $this->expectExceptionObject(DisabledFeature::inEnvironment('existing-env', 'disabled-feature'));
        $toggle->assertEnabled('disabled-feature');
    }

    public function testAssertDisabled(): void
    {
        $toggle = $this->createFeatureToggle();

        $toggle->assertDisabled('disabled-feature');

        $this->expectExceptionObject(EnabledFeature::inEnvironment('existing-env', 'enabled-feature'));
        $toggle->assertDisabled('enabled-feature');
    }

    private function createFeatureToggle(): FeatureToggle
    {
        return new RepositoryFeatureToggle($this->createRepository(), 'existing-env');
    }

    private function createRepository(): FeatureRepository
    {
        $repository = $this->createMock(FeatureRepository::class);

        $repository->method('find')->willReturnCallback(static function (string $environment, string $feature) {
            if ('existing-env' !== $environment) {
                throw UndefinedEnvironment::create($environment);
            }

            if ('disabled-feature' === $feature) {
                return new SimpleFeature('disabled-feature', State::DISABLED);
            }

            if ('enabled-feature' === $feature) {
                return new SimpleFeature('enabled-feature', State::ENABLED);
            }

            throw UndefinedFeature::inEnvironment($environment, $feature);
        });

        $repository->method('exists')->willReturnCallback(static function (string $environment, string $feature) {
            if ('existing-env' !== $environment) {
                throw UndefinedEnvironment::create($environment);
            }

            return in_array($feature, ['disabled-feature', 'enabled-feature']);
        });

        return $repository;
    }
}
