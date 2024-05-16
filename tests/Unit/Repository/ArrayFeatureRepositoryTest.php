<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\ArrayFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;

final class ArrayFeatureRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $repository = $this->createRepository();

        self::assertEquals([
            'disabled-feature' => new SimpleFeature('disabled-feature', State::ENABLED),
            'enabled-feature' => new SimpleFeature('enabled-feature', State::DISABLED),
        ], $repository->all('existing-env'));
    }

    public function testAllInUndefinedEnvironment(): void
    {
        $repository = $this->createRepository();
        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->all('undefined-env');
    }

    public function testFind(): void
    {
        $repository = $this->createRepository();
        self::assertEquals(new SimpleFeature('disabled-feature', State::ENABLED), $repository->find('existing-env', 'disabled-feature'));
        self::assertEquals(new SimpleFeature('enabled-feature', State::DISABLED), $repository->find('existing-env', 'enabled-feature'));

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('existing-env', 'undefined-feature'));
        $repository->find('existing-env', 'undefined-feature');
    }

    public function testFindInUndefinedEnvironment(): void
    {
        $repository = $this->createRepository();
        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->all('undefined-env');
    }

    public function testExists(): void
    {
        $repository = $this->createRepository();
        self::assertTrue($repository->exists('existing-env', 'disabled-feature'));
        self::assertTrue($repository->exists('existing-env', 'enabled-feature'));
        self::assertFalse($repository->exists('existing-env', 'undefined-feature'));
    }

    public function testExistsInUndefinedEnvironment(): void
    {
        $repository = $this->createRepository();
        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->exists('undefined-env', 'undefined-feature');
    }

    public function testAdd(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $repository = $this->createRepository($environmentRepository);
        $feature = new SimpleFeature('feature_1', State::ENABLED);
        $repository->add('existing-env', $feature);
        self::assertTrue($environment->hasFeature($feature));
    }

    public function testAddWithDuplicateFeature(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $repository = $this->createRepository($environmentRepository);
        $feature = new SimpleFeature('feature_1', State::ENABLED);
        $repository->add('existing-env', $feature);
        $this->expectExceptionObject(DuplicateFeature::inEnvironment('existing-env', 'feature_1'));
        $repository->add('existing-env', $feature);
    }

    public function testUpdate(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $environment->addFeature(new SimpleFeature('feature_1', State::DISABLED));

        $repository = $this->createRepository($environmentRepository);

        $repository->update('existing-env', new SimpleFeature('feature_1', State::ENABLED));
        self::assertTrue($environment->feature('feature_1')->state()->isEnabled());
    }

    public function testUpdateOnUndefinedFeature(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $repository = $this->createRepository($environmentRepository);

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('existing-env', 'feature_1'));
        $repository->update('existing-env', new SimpleFeature('feature_1', State::ENABLED));
    }

    public function testRemove(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $repository = $this->createRepository($environmentRepository);
        $feature = new SimpleFeature('feature_1', State::ENABLED);
        $environment->addFeature($feature);
        $repository->remove('existing-env', $feature);
        self::assertFalse($environment->hasFeature($feature));
    }

    public function testRemoveOnUndefinedFeature(): void
    {
        $environment = SimpleEnvironment::empty('existing-env');
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('find')->willReturn($environment);

        $repository = $this->createRepository($environmentRepository);

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('existing-env', 'feature_1'));
        $repository->remove('existing-env', new SimpleFeature('feature_1', State::ENABLED));
    }

    private function createRepository(?EnvironmentRepository $environmentRepository = null): FeatureRepository
    {
        if (null === $environmentRepository) {
            $environmentRepository = $this->createStub(EnvironmentRepository::class);
            $environmentRepository->method('find')->willReturnCallback(static function (string $name) {
                if ('existing-env' === $name) {
                    return new SimpleEnvironment('existing-env', [], [
                        new SimpleFeature('disabled-feature', State::ENABLED),
                        new SimpleFeature('enabled-feature', State::DISABLED),
                    ]);
                }

                throw UndefinedEnvironment::create($name);
            });
        }

        return new ArrayFeatureRepository($environmentRepository);
    }
}
