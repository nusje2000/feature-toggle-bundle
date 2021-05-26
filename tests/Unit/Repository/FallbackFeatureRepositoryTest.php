<?php

declare(strict_types=1);

namespace Unit\Repository;

use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\FallbackFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class FallbackFeatureRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $result = [
            '' => $this->createStub(Feature::class),
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('all')->willReturn($result);
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('all');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->all('environment'));
    }

    public function testAllWithFailingMainRepository(): void
    {
        $result = [
            '' => $this->createStub(Feature::class),
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('all')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::once())->method('all')->willReturn($result);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->all('environment'));
    }

    public function testFind(): void
    {
        $result = $this->createStub(Feature::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('find')->willReturn($result);
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('find');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->find('environment', 'feature'));
    }

    public function testFindWithFailingMainRepository(): void
    {
        $result = $this->createStub(Feature::class);

        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('find')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::once())->method('find')->willReturn($result);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->find('environment', 'feature'));
    }

    public function testExists(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('exists')->willReturn(true);
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('exists');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertTrue($repository->exists('environment', 'feature'));
    }

    public function testExistsWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('exists')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::once())->method('exists')->willReturn(true);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        self::assertTrue($repository->exists('environment', 'feature'));
    }

    public function testAdd(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('add');
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('add');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        $repository->add('environment', $this->createStub(Feature::class));
    }

    public function testAddWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('add')->willThrowException(new RuntimeException());
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('add');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);

        $this->expectExceptionObject(new RuntimeException());
        $repository->add('environment', $this->createStub(Feature::class));
    }

    public function testUpdate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('update');
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('update');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        $repository->update('environment', $this->createStub(Feature::class));
    }

    public function testUpdateWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('update')->willThrowException(new RuntimeException());
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('update');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);

        $this->expectExceptionObject(new RuntimeException());
        $repository->update('environment', $this->createStub(Feature::class));
    }

    public function testRemove(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('remove');
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('remove');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);
        $repository->remove('environment', $this->createStub(Feature::class));
    }

    public function testRemoveWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(FeatureRepository::class);
        $main->expects(self::once())->method('remove')->willThrowException(new RuntimeException());
        $fallback = $this->createMock(FeatureRepository::class);
        $fallback->expects(self::never())->method('remove');

        $repository = new FallbackFeatureRepository($logger, $main, $fallback);

        $this->expectExceptionObject(new RuntimeException());
        $repository->remove('environment', $this->createStub(Feature::class));
    }
}
