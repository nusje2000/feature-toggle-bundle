<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Will attempt fetching results using the main repository, but will use the fallback in cause this fails.
 *
 * Writing actions will still fail if the main repository is not accessable.
 */
final class FallbackFeatureRepository implements FeatureRepository
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FeatureRepository $main,
        private readonly FeatureRepository $fallback,
    ) {
    }

    public function all(string $environment): array
    {
        try {
            return $this->main->all($environment);
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->all($environment);
    }

    public function find(string $environment, string $feature): Feature
    {
        try {
            return $this->main->find($environment, $feature);
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->find($environment, $feature);
    }

    public function exists(string $environment, string $feature): bool
    {
        try {
            return $this->main->exists($environment, $feature);
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->exists($environment, $feature);
    }

    public function add(string $environment, Feature $feature): void
    {
        $this->main->add($environment, $feature);
    }

    public function update(string $environment, Feature $feature): void
    {
        $this->main->update($environment, $feature);
    }

    public function remove(string $environment, Feature $feature): void
    {
        $this->main->remove($environment, $feature);
    }

    private function logFailure(Throwable $throwable): void
    {
        $this->logger->error(sprintf(
            'Failed accessing "%s" due to: %s %s',
            get_class($this->main),
            get_class($throwable),
            $throwable->getMessage(),
        ));
    }
}
