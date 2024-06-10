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
    public function __construct(
        private readonly RequestParser $requestParser,
        private readonly FeatureRepository $repository,
    ) {
    }

    public function __invoke(Request $request, string $environment, string $name): Response
    {
        $feature = $this->repository->find($environment, $name);

        $json = $this->requestParser->json($request);
        $this->updateFeatureState($feature, $json);
        $this->updateFeatureDescription($feature, $json);
        $this->repository->update($environment, $feature);

        return new Response(sprintf('Updated feature named "%s" in environment "%s".', $name, $environment));
    }

    /**
     * @param array<mixed> $json
     */
    private function updateFeatureState(Feature $feature, array $json): void
    {
        if (!array_key_exists('enabled', $json)) {
            return;
        }

        $enabled = $json['enabled'];
        if (!is_bool($enabled)) {
            throw new BadRequestHttpException('Invalid feature state, please provide a boolean value for the "enabled" key.');
        }

        if ($enabled) {
            $feature->enable();

            return;
        }

        $feature->disable();
    }

    /**
     * @param array<mixed> $json
     */
    private function updateFeatureDescription(Feature $feature, array $json): void
    {
        if (!array_key_exists('description', $json)) {
            return;
        }

        $description = $json['description'];
        if (!is_null($description) && !is_string($description)) {
            throw new BadRequestHttpException('Invalid feature description, please provide a string or null value for the "description" key, or exclude the value from the json.');
        }

        if ($feature->description() !== $description) {
            $feature->setDescription($description);
        }
    }
}
