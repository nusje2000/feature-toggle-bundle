<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;

final class EnvironmentFeatureRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $repository = $this->createRepository();

        self::assertEquals([
            'disabled-feature' => new SimpleFeature('disabled-feature', State::ENABLED()),
            'enabled-feature' => new SimpleFeature('enabled-feature', State::DISABLED()),
        ], $repository->all('existing-env'));

        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->all('undefined-env');
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
        self::assertEquals(new SimpleFeature('disabled-feature', State::ENABLED()), $repository->find('existing-env', 'disabled-feature'));
        self::assertEquals(new SimpleFeature('enabled-feature', State::DISABLED()), $repository->find('existing-env', 'enabled-feature'));

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

    public function testRemove(): void
    {
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->expects(self::once())->method('find')->willReturn(
            new SimpleEnvironment('existing-env', [], [
                new SimpleFeature('feature_1', State::ENABLED()),
            ])
        );
        $environmentRepository->expects(self::once())->method('persist')->with(
            new SimpleEnvironment('existing-env', [], [])
        );

        $repository = $this->createRepository($environmentRepository);
        $repository->remove('existing-env', new SimpleFeature('feature_1', State::ENABLED()));
    }

    public function testExistsInUndefinedEnvironment(): void
    {
        $repository = $this->createRepository();
        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->exists('undefined-env', 'undefined-feature');
    }

    public function testPersist(): void
    {
        $environmentRepository = $this->createMock(EnvironmentRepository::class);

        $environmentRepository->expects(self::once())->method('find')->with('existing-env')->willReturn(new SimpleEnvironment('existing-env', [], [
            new SimpleFeature('feature-1', State::ENABLED()),
            new SimpleFeature('feature-2', State::ENABLED()),
        ]));

        $environmentRepository->expects(self::once())->method('persist')->with(new SimpleEnvironment('existing-env', [], [
            new SimpleFeature('feature-1', State::ENABLED()),
            new SimpleFeature('feature-2', State::ENABLED()),
            new SimpleFeature('new-feature', State::DISABLED()),
        ]));

        $repository = $this->createRepository($environmentRepository);
        $repository->persist('existing-env', new SimpleFeature('new-feature', State::DISABLED()));
    }

    public function testPersistInUndefinedEnvironment(): void
    {
        $repository = $this->createRepository();
        $this->expectExceptionObject(UndefinedEnvironment::create('undefined-env'));
        $repository->persist('undefined-env', new SimpleFeature('new-feature', State::DISABLED()));
    }

    private function createRepository(?EnvironmentRepository $environmentRepository = null): FeatureRepository
    {
        if (null === $environmentRepository) {
            $environmentRepository = $this->createStub(EnvironmentRepository::class);
            $environmentRepository->method('find')->willReturnCallback(static function (string $name) {
                if ('existing-env' === $name) {
                    return new SimpleEnvironment('existing-env', [], [
                        new SimpleFeature('disabled-feature', State::ENABLED()),
                        new SimpleFeature('enabled-feature', State::DISABLED()),
                    ]);
                }

                throw UndefinedEnvironment::create($name);
            });
        }

        return new EnvironmentFeatureRepository($environmentRepository);
    }
}
