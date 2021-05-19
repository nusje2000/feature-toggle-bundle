<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Cache;

final class FileStoreInvalidator implements Invalidator
{
    /**
     * @var string
     */
    private $storageLocation;

    public function __construct(string $storageLocation)
    {
        $this->storageLocation = $storageLocation;
    }

    public function invalidate(): void
    {
        rmdir($this->storageLocation);
    }
}
