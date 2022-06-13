<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Decorator;

use Nusje2000\FeatureToggleBundle\Decorator\CachingEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachingEnvironmentRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            $this->createEnvironment(),
        ]);

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            $this->createEnvironment(),
        ], $repository->all());
    }

    public function testAllWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment._all');
        $item->set([$this->createEnvironment()]);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::never())->method('all');

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            $this->createEnvironment(),
        ], $repository->all());
    }

    public function testAllWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment._all');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            $this->createEnvironment(),
        ]);

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            $this->createEnvironment(),
        ], $repository->all());
    }

    public function testAllWithInvalidArrayItemInCache(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment._all');
        $item->set([1]);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            $this->createEnvironment(),
        ]);

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            $this->createEnvironment(),
        ], $repository->all());
    }

    public function testFind(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('find')->willReturn($this->createEnvironment());

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createEnvironment(), $repository->find('some_env'));
    }

    public function testFindWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment.some_env');
        $item->set($this->createEnvironment());
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::never())->method('find');

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createEnvironment(), $repository->find('some_env'));
    }

    public function testFindWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment.some_env');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('find')->willReturn($this->createEnvironment());

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createEnvironment(), $repository->find('some_env'));
    }

    public function testExists(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('exists')->willReturn(true);

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env'));
    }

    public function testExistsWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment._exists.some_env');
        $item->set(true);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::never())->method('exists');

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env'));
    }

    public function testExistsWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.environment._exists.some_env');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('exists')->willReturn(true);

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env'));
    }

    public function testAdd(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('clear')->with('nusje2000_feature_toggle');

        $wrapped = $this->createMock(EnvironmentRepository::class);
        $wrapped->expects(self::once())->method('add')->with($this->createEnvironment());

        $repository = new CachingEnvironmentRepository($adapter, $wrapped, $logger);

        $repository->add($this->createEnvironment());
    }

    private function createEnvironment(): Environment
    {
        return $this->createStub(Environment::class);
    }
}
