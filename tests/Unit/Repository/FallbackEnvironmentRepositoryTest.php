<?php

declare(strict_types=1);

namespace Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FallbackEnvironmentRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class FallbackEnvironmentRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $result = [
            $this->createStub(Environment::class),
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('all')->willReturn($result);
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::never())->method('all');

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->all());
    }

    public function testAllWithFailingMainRepository(): void
    {
        $result = [
            $this->createStub(Environment::class),
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('all')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::once())->method('all')->willReturn($result);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->all());
    }

    public function testFind(): void
    {
        $result = $this->createStub(Environment::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('find')->willReturn($result);
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::never())->method('find');

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->find('environment'));
    }

    public function testFindWithFailingMainRepository(): void
    {
        $result = $this->createStub(Environment::class);

        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('find')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::once())->method('find')->willReturn($result);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertSame($result, $repository->find('environment'));
    }

    public function testExists(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('exists')->willReturn(true);
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::never())->method('exists');

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertTrue($repository->exists('environment'));
    }

    public function testExistsWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('exists')->willThrowException(new RuntimeException('Some exception'));
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::once())->method('exists')->willReturn(true);
        $logger->expects(self::once())->method('error')->with(sprintf('Failed accessing "%s" due to: RuntimeException Some exception', get_class($main)));

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        self::assertTrue($repository->exists('environment'));
    }

    public function testAdd(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('add');
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::never())->method('add');

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);
        $repository->add($this->createStub(Environment::class));
    }

    public function testAddWithFailingMainRepository(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $main = $this->createMock(EnvironmentRepository::class);
        $main->expects(self::once())->method('add')->willThrowException(new RuntimeException());
        $fallback = $this->createMock(EnvironmentRepository::class);
        $fallback->expects(self::never())->method('add');

        $repository = new FallbackEnvironmentRepository($logger, $main, $fallback);

        $this->expectExceptionObject(new RuntimeException());
        $repository->add($this->createStub(Environment::class));
    }
}
