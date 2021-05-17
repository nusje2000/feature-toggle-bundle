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
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use function Safe\sprintf;

final class CreateController
{
    /**
     * @var RequestParser
     */
    private $requestParser;

    /**
     * @var EnvironmentRepository
     */
    private $repository;

    public function __construct(RequestParser $requestParser, EnvironmentRepository $repository)
    {
        $this->requestParser = $requestParser;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $json = $this->requestParser->json($request);
        $environment = $this->createEnvironmentFromJson($json);
        $name = $environment->name();

        if ($this->repository->exists($name)) {
            throw new ConflictHttpException(sprintf('Environment with name "%s" already exists.', $name));
        }

        $this->repository->persist($environment);

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

        /** @var mixed $host */
        $host = $json['host'] ?? null;
        if (!is_string($host)) {
            throw new BadRequestHttpException('Missing/Invalid environment host, please provide a string value for the "host" key.');
        }

        $environment = SimpleEnvironment::empty($name);
        $environment->addHost($host);

        return $environment;
    }
}
