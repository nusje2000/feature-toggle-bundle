<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Cache;

use Nusje2000\FeatureToggleBundle\Cache\FileStoreInvalidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileStoreInvalidatorTest extends TestCase
{
    public function testInvalidate(): void
    {
        $dir = 'some-dir-name';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())->method('remove')->with($dir);

        /** @psalm-suppress DeprecatedClass */
        $invalidator = new FileStoreInvalidator($dir, $filesystem);
        $invalidator->invalidate();
    }
}
