<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @deprecated http cache support will be dropped from 2.0
 */
final class FileStoreInvalidator implements Invalidator
{
    public function __construct(
        private readonly string $storageLocation,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function invalidate(): void
    {
        $this->filesystem->remove($this->storageLocation);
    }
}
