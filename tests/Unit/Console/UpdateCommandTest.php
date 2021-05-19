<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Console;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Console\UpdateCommand;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class UpdateCommandTest extends TestCase
{
    public function testRun(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('hasOption')->with('dry-run')->willReturn(false);

        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(false);
        $environmentRepository->expects(self::once())->method('add')->with(
            new SimpleEnvironment('environment', ['host_1', 'host_2'], [])
        );

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('exists')->willReturn(false);
        $featureRepository->expects(self::exactly(2))->method('add')->withConsecutive(
            ['environment', new SimpleFeature('enabled_feature', State::ENABLED())],
            ['environment', new SimpleFeature('disabled_feature', State::DISABLED())]
        );

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::once())->method('invalidate');

        $command = new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED()),
            new SimpleFeature('disabled_feature', State::DISABLED()),
        ]));

        $command->run($input, new BufferedOutput());
    }

    public function testRunOnExistingEnvironment(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('hasOption')->with('dry-run')->willReturn(false);

        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $environmentRepository->expects(self::never())->method('add');

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('exists')->willReturnOnConsecutiveCalls(true, false);
        $featureRepository->expects(self::once())->method('add')->withConsecutive(
            ['environment', new SimpleFeature('disabled_feature', State::DISABLED())]
        );

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::once())->method('invalidate');

        $command = new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED()),
            new SimpleFeature('disabled_feature', State::DISABLED()),
        ]));

        $command->run($input, new BufferedOutput());
    }

    public function testDryRun(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('hasOption')->with('dry-run')->willReturn(true);

        $environmentRepository = $this->createMock(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(false);
        $environmentRepository->expects(self::never())->method('add');
        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->expects(self::never())->method('add');

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED()),
            new SimpleFeature('disabled_feature', State::DISABLED()),
        ]));

        $output = new BufferedOutput();
        $command->run($input, $output);

        self::assertSame([
            '',
            '! [CAUTION] The following actions should be taken:',
            '',
            'Create environment "environment".',
            'Create feature "enabled_feature" with default state "ENABLED".',
            'Create feature "disabled_feature" with default state "DISABLED".',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    public function testDryRunWithExistingEnvironment(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('hasOption')->with('dry-run')->willReturn(true);

        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $featureRepository = $this->createStub(FeatureRepository::class);

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED()),
            new SimpleFeature('disabled_feature', State::DISABLED()),
        ]));

        $output = new BufferedOutput();
        $command->run($input, $output);

        self::assertSame([
            '',
            '! [CAUTION] The following actions should be taken:',
            '',
            'Create feature "enabled_feature" with default state "ENABLED".',
            'Create feature "disabled_feature" with default state "DISABLED".',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    public function testDryRunWithUpToDateEnvironment(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('hasOption')->with('dry-run')->willReturn(true);

        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $environmentRepository->method('exists')->willReturn(true);
        $featureRepository = $this->createStub(FeatureRepository::class);
        $featureRepository->method('exists')->willReturn(true);

        $invalidator = $this->createMock(Invalidator::class);
        $invalidator->expects(self::never())->method('invalidate');

        $command = new UpdateCommand($environmentRepository, $featureRepository, $invalidator, new SimpleEnvironment('environment', ['host_1', 'host_2'], [
            new SimpleFeature('enabled_feature', State::ENABLED()),
            new SimpleFeature('disabled_feature', State::DISABLED()),
        ]));

        $output = new BufferedOutput();
        $command->run($input, $output);

        self::assertSame([
            '',
            '[OK] The environment is up to date.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }
}
