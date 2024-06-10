<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Will attempt fetching results using the main repository, but will use the fallback in cause this fails.
 *
 * Writing actions will still fail if the main repository is not accessable.
 */
final class FallbackEnvironmentRepository implements EnvironmentRepository
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EnvironmentRepository $main,
        private readonly EnvironmentRepository $fallback,
    ) {
    }

    public function all(): array
    {
        try {
            return $this->main->all();
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->all();
    }

    public function find(string $environment): Environment
    {
        try {
            return $this->main->find($environment);
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->find($environment);
    }

    public function exists(string $environment): bool
    {
        try {
            return $this->main->exists($environment);
        } catch (Throwable $throwable) {
            $this->logFailure($throwable);
        }

        return $this->fallback->exists($environment);
    }

    public function add(Environment $environment): void
    {
        $this->main->add($environment);
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
