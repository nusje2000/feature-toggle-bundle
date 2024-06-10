<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Decorator;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class CachingEnvironmentRepository implements EnvironmentRepository
{
    private const CACHE_PREFIX = 'nusje2000_feature_toggle';
    private const CACHE_ENVIRONMENT_KEY = 'environment';
    private const CACHE_ALL_SECTION = '_all';
    private const CACHE_EXISTS_SECTION = '_exists';

    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly EnvironmentRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function all(): array
    {
        $key = $this->createKey(self::CACHE_ENVIRONMENT_KEY, self::CACHE_ALL_SECTION);
        $item = $this->adapter->getItem($key);

        if ($item->isHit()) {
            $this->logger->info(sprintf('Attempting to use cache with key "%s".', $item->getKey()));

            /** @var mixed $value */
            $value = $item->get();

            if ($this->isValidEnvironmentArray($value)) {
                $this->logger->info(sprintf('Cache item "%s" is valid.', $item->getKey()));

                return array_values($value);
            }

            $this->logger->warning(sprintf('Cache item "%s" is invalid.', $item->getKey()));
        }

        $this->logger->info(sprintf('No usable cache item found, fetching from repository "%s".', get_class($this->repository)));
        $result = $this->repository->all();
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function find(string $environment): Environment
    {
        $key = $this->createKey(self::CACHE_ENVIRONMENT_KEY, $environment);
        $item = $this->adapter->getItem($key);

        if ($item->isHit()) {
            $this->logger->info(sprintf('Attempting to use cache with key "%s".', $item->getKey()));

            /** @var mixed $value */
            $value = $item->get();

            if ($value instanceof Environment) {
                $this->logger->info(sprintf('Cache item "%s" is valid.', $item->getKey()));

                return $value;
            }

            $this->logger->warning(sprintf('Cache item "%s" is invalid.', $item->getKey()));
        }

        $this->logger->info(sprintf('No usable cache item found, fetching from repository "%s".', get_class($this->repository)));
        $result = $this->repository->find($environment);
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function exists(string $environment): bool
    {
        $key = $this->createKey(self::CACHE_ENVIRONMENT_KEY, self::CACHE_EXISTS_SECTION, $environment);
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
        $result = $this->repository->exists($environment);
        $item->set($result);
        $this->logger->info('Saving fetched result to cache.');
        $this->adapter->save($item);

        return $result;
    }

    public function add(Environment $environment): void
    {
        $this->repository->add($environment);
        $this->adapter->clear(self::CACHE_PREFIX);
    }

    /**
     * @psalm-assert-if-true array<Environment> $list
     */
    private function isValidEnvironmentArray(mixed $list): bool
    {
        if (!is_array($list)) {
            return false;
        }

        foreach ($list as $item) {
            if (!$item instanceof Environment) {
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
