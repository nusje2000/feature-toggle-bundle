<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ViewController
{
    public function __construct(
        private readonly FeatureRepository $repository,
    ) {
    }

    public function __invoke(string $environment, string $name): Response
    {
        $feature = $this->repository->find($environment, $name);

        $response = new JsonResponse([
            'name' => $feature->name(),
            'enabled' => $feature->state()->isEnabled(),
            'description' => $feature->description(),
        ]);

        $response->setCache([
            'public' => true,
            'max_age' => 86400,
        ]);

        return $response;
    }
}
