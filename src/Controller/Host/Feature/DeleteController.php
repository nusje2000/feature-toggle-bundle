<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\HttpFoundation\Response;

final class DeleteController
{
    public function __construct(
        private readonly FeatureRepository $repository,
    ) {
    }

    public function __invoke(string $environment, string $name): Response
    {
        $feature = $this->repository->find($environment, $name);
        $this->repository->remove($environment, $feature);

        return new Response(null, Response::HTTP_OK);
    }
}
