<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Console;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Safe\sprintf;

final class UpdateCommand extends Command
{
    protected static $defaultName = 'feature-toggle:update';

    /**
     * @var EnvironmentRepository
     */
    private $environmentRepository;

    /**
     * @var FeatureRepository
     */
    private $featureRepository;

    /**
     * @var Invalidator
     */
    private $invalidator;

    /**
     * @var Environment
     */
    private $defaultEnvironment;

    public function __construct(
        EnvironmentRepository $environmentRepository,
        FeatureRepository $featureRepository,
        Invalidator $invalidator,
        Environment $defaultEnvironment,
        string $name = null
    ) {
        parent::__construct($name);
        $this->environmentRepository = $environmentRepository;
        $this->featureRepository = $featureRepository;
        $this->invalidator = $invalidator;
        $this->defaultEnvironment = $defaultEnvironment;
    }

    protected function configure(): void
    {
        $this->setDescription('Registers the environment and new features to the repository.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Will only output a list of actions that will be taken.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->hasOption('dry-run')) {
            $this->dryRun($io);

            return 0;
        }

        $this->invalidator->invalidate();

        if (!$this->environmentRepository->exists($this->defaultEnvironment->name())) {
            $environment = new SimpleEnvironment($this->defaultEnvironment->name(), $this->defaultEnvironment->hosts(), []);
            $this->environmentRepository->add($environment);
        }

        foreach ($this->defaultEnvironment->features() as $feature) {
            if (!$this->featureRepository->exists($this->defaultEnvironment->name(), $feature->name())) {
                $this->featureRepository->add($this->defaultEnvironment->name(), $feature);
            }
        }

        $io->success('Environment has been updated.');

        return 0;
    }

    private function dryRun(SymfonyStyle $io): void
    {
        $actions = [];

        $environmentExists = $this->environmentRepository->exists($this->defaultEnvironment->name());
        if (!$environmentExists) {
            $actions[] = sprintf(sprintf('Create environment "%s".', $this->defaultEnvironment->name()));
        }

        foreach ($this->defaultEnvironment->features() as $feature) {
            if ($environmentExists && $this->featureRepository->exists($this->defaultEnvironment->name(), $feature->name())) {
                continue;
            }

            $actions[] = sprintf(sprintf('Create feature "%s" with default state "%s".', $feature->name(), $feature->state()->getKey()));
        }

        if (count($actions) < 1) {
            $io->success('The environment is up to date.');

            return;
        }

        $io->caution('The following actions should be taken:');
        $io->writeln($actions);
    }
}