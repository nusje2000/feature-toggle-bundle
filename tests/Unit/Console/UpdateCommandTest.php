<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Console;

use Generator;
use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Console\UpdateCommand;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class UpdateCommandTest extends TestCase
{
    public function testRun(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->with('dry-run')->willReturn(false);

        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(false);
        $environmentRepository->expects(self::once())->method('add')->with(
            new SimpleEnvironment('environment', ['host_1', 'host_2'], [])
        );

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('exists')->willReturn(false);

        $expectedAddedFeaturesGenerator = (static function (): Generator {
            yield ['environment', new SimpleFeature('enabled_feature', State::ENABLED)];
            yield ['environment', new SimpleFeature('disabled_feature', State::DISABLED)];
        })();
        $featureRepository->expects(self::exactly(2))
            ->method('add')
            ->willReturnCallback(static function (mixed ...$args) use ($expectedAddedFeaturesGenerator) {
                $expectedArguments = $expectedAddedFeaturesGenerator->current();
                self::assertEquals($expectedArguments, $args);

                $expectedAddedFeaturesGenerator->next();
            });

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::once())->method('invalidate');

        $command = $this->command($environmentRepository, $featureRepository, $invalidator);

        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame([
            'Checking environment "environment".',
            'Creating environment "environment".',
            'Checking feature "enabled_feature".',
            'Creating feature "enabled_feature".',
            'Checking feature "disabled_feature".',
            'Creating feature "disabled_feature".',
            '',
            '[OK] Environment has been updated.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $tester->getDisplay())));
    }

    public function testRunOnExistingEnvironment(): void
    {
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $environmentRepository->expects(self::never())->method('add');

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('exists')->willReturnOnConsecutiveCalls(true, false);

        $expectedAddedFeaturesGenerator = (static function (): Generator {
            yield ['environment', new SimpleFeature('disabled_feature', State::DISABLED)];
        })();
        $featureRepository->expects(self::once())
            ->method('add')
            ->willReturnCallback(static function (mixed ...$args) use ($expectedAddedFeaturesGenerator) {
                $expectedArguments = $expectedAddedFeaturesGenerator->current();
                self::assertEquals($expectedArguments, $args);

                $expectedAddedFeaturesGenerator->next();
            });

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::once())->method('invalidate');

        $command = $this->command($environmentRepository, $featureRepository, $invalidator);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testDryRun(): void
    {
        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(false);
        $environmentRepository->expects(self::never())->method('add');
        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->expects(self::never())->method('add');

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = $this->command($environmentRepository, $featureRepository, $invalidator);

        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => null]);

        self::assertSame([
            '',
            '! [CAUTION] The following actions should be taken:',
            '',
            'Create environment "environment".',
            'Create feature "enabled_feature" with default state "ENABLED".',
            'Create feature "disabled_feature" with default state "DISABLED".',
            '',
        ], array_map('trim', explode(PHP_EOL, $tester->getDisplay())));
    }

    public function testDryRunWithExistingEnvironment(): void
    {
        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $featureRepository = $this->createStub(FeatureRepository::class);

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = $this->command($environmentRepository, $featureRepository, $invalidator);

        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => null]);

        self::assertSame([
            '',
            '! [CAUTION] The following actions should be taken:',
            '',
            'Create feature "enabled_feature" with default state "ENABLED".',
            'Create feature "disabled_feature" with default state "DISABLED".',
            '',
        ], array_map('trim', explode(PHP_EOL, $tester->getDisplay())));
    }

    public function testDryRunWithUpToDateEnvironment(): void
    {
        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $featureRepository = $this->createStub(FeatureRepository::class);
        $featureRepository->method('exists')->willReturn(true);

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = $this->command($environmentRepository, $featureRepository, $invalidator);

        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => null]);

        self::assertSame([
            '',
            '[OK] The environment is up to date.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $tester->getDisplay())));
    }

    private function command(EnvironmentRepository $environmentRepository, FeatureRepository $featureRepository, Invalidator $invalidator): UpdateCommand
    {
        return new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED),
            new SimpleFeature('disabled_feature', State::DISABLED),
        ]));
    }
}
