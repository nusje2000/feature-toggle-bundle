<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Console;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Console\CleanupCommand;
use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class CleanupCommandTest extends TestCase
{
    public function testRunWithUpToDateEnvironment(): void
    {
        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('all')->with('default_environment')->willReturn([
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED()),
        ]);
        $featureRepository->expects(self::never())->method('remove');

        $command = $this->createCommand($featureRepository);

        $input = $this->createInput(false);
        $output = new BufferedOutput();

        $command->run($input, $output);

        self::assertSame([
            '',
            '[OK] Environment is up to date, no features where removed.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    public function testRunWithRemovableFeatures(): void
    {
        $removableFeature = new SimpleFeature('feature_3', State::DISABLED());

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('all')->with('default_environment')->willReturn([
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED()),
            $removableFeature,
        ]);
        $featureRepository->expects(self::once())->method('remove')->with('default_environment', $removableFeature);

        $command = $this->createCommand($featureRepository);

        $input = $this->createInput(false);
        $output = new BufferedOutput();

        $command->run($input, $output);

        self::assertSame([
            'Remove "feature_3".',
            '',
            '[OK] 1 features where removed.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    public function testDryRunWithUpToDateEnvironment(): void
    {
        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('all')->with('default_environment')->willReturn([
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED()),
        ]);
        $featureRepository->expects(self::never())->method('remove');

        $command = $this->createCommand($featureRepository);

        $input = $this->createInput(true);
        $output = new BufferedOutput();

        $command->run($input, $output);

        self::assertSame([
            '',
            '[OK] Environment is up to date, no features where removed.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    public function testDryRunWithRemovableFeatures(): void
    {
        $removableFeature = new SimpleFeature('feature_3', State::DISABLED());

        $featureRepository = $this->createMock(FeatureRepository::class);
        $featureRepository->method('all')->with('default_environment')->willReturn([
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED()),
            $removableFeature,
        ]);
        $featureRepository->expects(self::never())->method('remove');

        $input = $this->createInput(true);

        $command = $this->createCommand($featureRepository);

        $output = new BufferedOutput();
        $command->run($input, $output);

        self::assertSame([
            'Remove "feature_3".',
            '',
            '[OK] 1 features can be removed, remove the --dry-run option to execute these removals.',
            '',
            '',
        ], array_map('trim', explode(PHP_EOL, $output->fetch())));
    }

    protected function createCommand(FeatureRepository $featureRepository): CleanupCommand
    {
        return new CleanupCommand($featureRepository, $this->createStub(Invalidator::class), $this->createDefaultEnvironment());
    }

    private function createDefaultEnvironment(): Environment
    {
        return new SimpleEnvironment('default_environment', [], [
            new SimpleFeature('feature_1', State::ENABLED()),
            new SimpleFeature('feature_2', State::DISABLED()),
        ]);
    }

    private function createInput(bool $dryRun): InputInterface
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('getOption')->with('dry-run')->willReturn($dryRun);

        return $input;
    }
}
