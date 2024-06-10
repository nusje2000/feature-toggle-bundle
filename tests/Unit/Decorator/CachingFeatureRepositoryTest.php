<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Decorator;

use Nusje2000\FeatureToggleBundle\Decorator\CachingFeatureRepository;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class CachingFeatureRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            'some_feature' => $this->createFeature(),
        ]);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            'some_feature' => $this->createFeature(),
        ], $repository->all('some_env'));
    }

    public function testAllWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._all');
        $item->set([
            'some_feature' => $this->createFeature(),
        ]);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::never())->method('all');

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            'some_feature' => $this->createFeature(),
        ], $repository->all('some_env'));
    }

    public function testAllWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._all');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            'some_feature' => $this->createFeature(),
        ]);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            'some_feature' => $this->createFeature(),
        ], $repository->all('some_env'));
    }

    public function testAllWithInvalidArrayItemInCache(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._all');
        $item->set(['feature' => 1]);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            'some_feature' => $this->createFeature(),
        ]);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            'some_feature' => $this->createFeature(),
        ], $repository->all('some_env'));
    }

    public function testAllWithInvalidArrayKeyInCache(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._all');
        $item->set([1 => $this->createFeature()]);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('all')->willReturn([
            'some_feature' => $this->createFeature(),
        ]);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals([
            'some_feature' => $this->createFeature(),
        ], $repository->all('some_env'));
    }

    public function testFind(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('find')->willReturn($this->createFeature());

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createFeature(), $repository->find('some_env', 'some_feature'));
    }

    public function testFindWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env.some_feature');
        $item->set($this->createFeature());
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::never())->method('find');

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createFeature(), $repository->find('some_env', 'some_feature'));
    }

    public function testFindWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env.some_feature');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('find')->willReturn($this->createFeature());

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals($this->createFeature(), $repository->find('some_env', 'some_feature'));
    }

    public function testExists(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('exists')->willReturn(true);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env', 'some_feature'));
    }

    public function testExistsWithCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._exists.some_feature');
        $item->set(true);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::never())->method('exists');

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env', 'some_feature'));
    }

    public function testExistsWithInvalidCachedItem(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new ArrayAdapter();
        $item = $adapter->getItem('nusje2000_feature_toggle.feature.some_env._exists.some_feature');
        $item->set(1);
        $adapter->save($item);

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('exists')->willReturn(true);

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);
        self::assertEquals(true, $repository->exists('some_env', 'some_feature'));
    }

    public function testAdd(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('clear')->with('nusje2000_feature_toggle');

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('add')->with('some_env', $this->createFeature());

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);

        $repository->add('some_env', $this->createFeature());
    }

    public function testUpdate(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('clear')->with('nusje2000_feature_toggle');

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('update')->with('some_env', $this->createFeature());

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);

        $repository->update('some_env', $this->createFeature());
    }

    public function testRemove(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('clear')->with('nusje2000_feature_toggle');

        $wrapped = $this->createMock(FeatureRepository::class);
        $wrapped->expects(self::once())->method('remove')->with('some_env', $this->createFeature());

        $repository = new CachingFeatureRepository($adapter, $wrapped, $logger);

        $repository->remove('some_env', $this->createFeature());
    }

    private function createAdapterWithItem(ItemInterface $item): AdapterInterface
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())->method('getItem')->with($item->getKey())->willReturn($item);

        return $adapter;
    }

    private function createCacheItem(string $key, mixed $value, bool $expectUpdate): ItemInterface
    {
        $item = $this->createMock(ItemInterface::class);
        $item->method('getKey')->willReturn($key);
        $item->method('isHit')->willReturn(null !== $value);
        $item->method('get')->willReturn($value);
        $item->expects($expectUpdate ? self::once() : self::never())->method('set');

        return $item;
    }

    private function createFeature(): Feature
    {
        return $this->createStub(Feature::class);
    }
}
