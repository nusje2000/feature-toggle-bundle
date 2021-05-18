<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Repository\ArrayEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use PHPUnit\Framework\TestCase;

final class ArrayEnvironmentRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $repository = $this->createRepository();

        self::assertEquals([
            $this->createEnvironment('env-1'),
            $this->createEnvironment('env-2'),
            $this->createEnvironment('env-3'),
        ], $repository->all());
    }

    public function testFind(): void
    {
        $repository = $this->createRepository();
        self::assertEquals($this->createEnvironment('env-1'), $repository->find('env-1'));
        $this->expectExceptionObject(UndefinedEnvironment::create('env-4'));
        $repository->find('env-4');
    }

    public function testExists(): void
    {
        $repository = $this->createRepository();

        self::assertFalse($repository->exists('env-0'));
        self::assertTrue($repository->exists('env-1'));
        self::assertTrue($repository->exists('env-2'));
        self::assertTrue($repository->exists('env-3'));
        self::assertFalse($repository->exists('env-4'));
    }

    public function testAdd(): void
    {
        $repository = $this->createRepository();
        $repository->add(SimpleEnvironment::empty('env'));
        $this->expectExceptionObject(DuplicateEnvironment::create('env'));
        $repository->add(SimpleEnvironment::empty('env'));
    }

    private function createRepository(): EnvironmentRepository
    {
        return new ArrayEnvironmentRepository([
            $this->createEnvironment('env-1'),
            $this->createEnvironment('env-2'),
            $this->createEnvironment('env-3'),
        ]);
    }

    private function createEnvironment(string $name): Environment
    {
        return SimpleEnvironment::empty($name);
    }
}
