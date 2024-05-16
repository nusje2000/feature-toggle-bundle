<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Console;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CleanupCommand extends Command
{
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
        FeatureRepository $featureRepository,
        Invalidator $invalidator,
        Environment $defaultEnvironment
    ) {
        parent::__construct();

        $this->featureRepository = $featureRepository;
        $this->invalidator = $invalidator;
        $this->defaultEnvironment = $defaultEnvironment;
    }

    protected function configure(): void
    {
        $this->setName('feature-toggle:cleanup');
        $this->setDescription('Removes redundant features from the repository.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Will only output a list of actions that will be taken.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->invalidator->invalidate();

        $dryRun = $input->getOption('dry-run');

        $io = new SymfonyStyle($input, $output);

        $removals = 0;

        $environment = $this->defaultEnvironment->name();
        foreach ($this->featureRepository->all($environment) as $feature) {
            if (!$this->defaultEnvironment->hasFeature($feature)) {
                $io->writeln(sprintf('Remove "%s".', $feature->name()));
                $removals++;

                if (!$dryRun) {
                    $this->featureRepository->remove($environment, $feature);
                }
            }
        }

        if (0 === $removals) {
            $io->success('Environment is up to date, no features where removed.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $io->success(sprintf('%d features can be removed, remove the --dry-run option to execute these removals.', $removals));

            return self::SUCCESS;
        }

        $io->success(sprintf('%d features where removed.', $removals));

        return self::SUCCESS;
    }
}
