<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Cache;

use Nusje2000\FeatureToggleBundle\Cache\FileStoreInvalidator;
use PHPUnit\Framework\TestCase;

final class FileStoreInvalidatorTest extends TestCase
{
    public function testInvalidate(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('', true);
        mkdir($dir);

        self::assertDirectoryExists($dir);
        $invalidator = new FileStoreInvalidator($dir);
        $invalidator->invalidate();
        self::assertDirectoryDoesNotExist($dir);
    }
}
