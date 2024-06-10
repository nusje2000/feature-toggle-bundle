<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateController
{
    public function __construct(
        private readonly RequestParser $requestParser,
        private readonly EnvironmentRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $json = $this->requestParser->json($request);
        $environment = $this->createEnvironmentFromJson($json);
        $name = $environment->name();

        $this->repository->add($environment);

        return new Response(sprintf('Created environment named "%s".', $name));
    }

    /**
     * @param array<mixed> $json
     */
    private function createEnvironmentFromJson(array $json): Environment
    {
        /** @var mixed $name */
        $name = $json['name'] ?? null;
        if (!is_string($name)) {
            throw new BadRequestHttpException('Missing/Invalid environment name, please provide a string value for the "name" key.');
        }

        $environment = SimpleEnvironment::empty($name);

        $hosts = $json['hosts'] ?? null;
        if (!is_array($hosts)) {
            throw new BadRequestHttpException('Missing/Invalid environment host, please provide an array of strings for the "host" key.');
        }

        foreach ($hosts as $host) {
            if (!is_string($host)) {
                throw new BadRequestHttpException('Missing/Invalid environment host, please provide an array of strings for the "host" key.');
            }

            $environment->addHost($host);
        }

        return $environment;
    }
}
