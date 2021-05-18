<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ViewController
{
    /**
     * @var EnvironmentRepository
     */
    private $repository;

    public function __construct(EnvironmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $name): Response
    {
        $environment = $this->repository->find($name);

        return new JsonResponse([
            'name' => $environment->name(),
            'hosts' => $environment->hosts(),
            'features' => array_map(
                static function (Feature $feature) {
                    return [
                        'name' => $feature->name(),
                        'enabled' => $feature->state()->isEnabled(),
                    ];
                },
                array_values($environment->features())
            ),
        ]);
    }
}
