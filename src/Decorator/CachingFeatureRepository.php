<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Decorator;

use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class CachingFeatureRepository implements FeatureRepository
{
    private const CACHE_PREFIX = 'nusje2000_feature_toggle';
    private const CACHE_FEATURE_KEY = 'feature';
    private const CACHE_ALL_SECTION = '_all';
    private const CACHE_EXISTS_SECTION = '_exists';

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly FeatureRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function all(string $environment): array
    {
        $key = $this->createKey(self::CACHE_FEATURE_KEY, $environment, self::CACHE_ALL_SECTION);
        $item = $this->adapter->getItem($key);

        if ($item->isHit()) {
            $this->logger->info(sprintf('Attempting to use cache with key "%s".', $item->getKey()));

            /** @var mixed $value */
            $value = $item->get();

            if ($this->isValidFeatureArray($value)) {
                $this->logger->info(sprintf('Cache item "%s" is valid.', $item->getKey()));

                return $value;
            }

            $this->logger->warning(sprintf('Cache item "%s" is invalid.', $item->getKey()));
        }

        $this->logger->info(sprintf('No usable cache item found, fetching from repository "%s".', get_class($this->repository)));
        $result = $this->repository->all($environment);
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function find(string $environment, string $feature): Feature
    {
        $key = $this->createKey(self::CACHE_FEATURE_KEY, $environment, $feature);
        $item = $this->adapter->getItem($key);

        if ($item->isHit()) {
            $this->logger->info(sprintf('Attempting to use cache with key "%s".', $item->getKey()));

            /** @var mixed $value */
            $value = $item->get();

            if ($value instanceof Feature) {
                $this->logger->info(sprintf('Cache item "%s" is valid.', $item->getKey()));

                return $value;
            }

            $this->logger->warning(sprintf('Cache item "%s" is invalid.', $item->getKey()));
        }

        $this->logger->info(sprintf('No usable cache item found, fetching from repository "%s".', get_class($this->repository)));
        $result = $this->repository->find($environment, $feature);
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function exists(string $environment, string $feature): bool
    {
        $key = $this->createKey(self::CACHE_FEATURE_KEY, $environment, self::CACHE_EXISTS_SECTION, $feature);
        $item = $this->adapter->getItem($key);

        if ($item->isHit()) {
            $this->logger->info(sprintf('Attempting to use cache with key "%s".', $item->getKey()));

            /** @var mixed $value */
            $value = $item->get();

            if (is_bool($value)) {
                $this->logger->info(sprintf('Cache item "%s" is valid.', $item->getKey()));

                return $value;
            }

            $this->logger->warning(sprintf('Cache item "%s" is invalid.', $item->getKey()));
        }

        $this->logger->info(sprintf('No usable cache item found, fetching from repository "%s".', get_class($this->repository)));
        $result = $this->repository->exists($environment, $feature);
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function add(string $environment, Feature $feature): void
    {
        $this->repository->add($environment, $feature);
        $this->adapter->clear(self::CACHE_PREFIX);
    }

    public function update(string $environment, Feature $feature): void
    {
        $this->repository->update($environment, $feature);
        $this->adapter->clear(self::CACHE_PREFIX);
    }

    public function remove(string $environment, Feature $feature): void
    {
        $this->repository->remove($environment, $feature);
        $this->adapter->clear(self::CACHE_PREFIX);
    }

    /**
     * @psalm-assert-if-true array<string, Feature> $list
     */
    private function isValidFeatureArray(mixed $list): bool
    {
        if (!is_array($list)) {
            return false;
        }

        foreach ($list as $key => $item) {
            if (!is_string($key)) {
                return false;
            }

            if (!$item instanceof Feature) {
                return false;
            }
        }

        return true;
    }

    private function createKey(string ...$parts): string
    {
        array_unshift($parts, self::CACHE_PREFIX);

        return implode('.', $parts);
    }
}
