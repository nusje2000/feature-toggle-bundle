<?php

declare(strict_types=1);

namespace Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
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

    public function testPersist(): void
    {
        $repository = $this->createRepository();

        self::assertFalse($repository->exists('env-0'));
        $repository->persist($this->createEnvironment('env-0'));
        self::assertTrue($repository->exists('env-0'));
        self::assertEquals($this->createEnvironment('env-0'), $repository->find('env-0'));
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
        return new SimpleEnvironment($name, []);
    }
}
