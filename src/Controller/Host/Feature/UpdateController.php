<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateController
{
    /**
     * @var FeatureRepository
     */
    private $repository;

    /**
     * @var RequestParser
     */
    private $requestParser;

    public function __construct(RequestParser $requestParser, FeatureRepository $repository)
    {
        $this->repository = $repository;
        $this->requestParser = $requestParser;
    }

    public function __invoke(Request $request, string $environment, string $name): Response
    {
        $feature = $this->repository->find($environment, $name);

        $json = $this->requestParser->json($request);
        $this->updateFeature($feature, $json);
        $this->repository->update($environment, $feature);

        return new Response(sprintf('Updated feature named "%s" in environment "%s".', $name, $environment));
    }

    /**
     * @param array<mixed> $json
     */
    private function updateFeature(Feature $feature, array $json): void
    {
        $enabled = $json['enabled'] ?? null;
        if (!is_bool($enabled)) {
            throw new BadRequestHttpException('Missing/Invalid feature state, please provide a boolean value for the "enabled" key.');
        }

        if ($enabled) {
            $feature->enable();

            return;
        }

        $feature->disable();
    }
}
