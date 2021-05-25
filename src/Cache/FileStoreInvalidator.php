<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;

final class FileStoreInvalidator implements Invalidator
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $storageLocation;

    public function __construct(string $storageLocation, ?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
        $this->storageLocation = $storageLocation;
    }

    public function invalidate(): void
    {
        $this->filesystem->remove($this->storageLocation);
    }
}
