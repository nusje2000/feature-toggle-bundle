<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Safe\Exceptions\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use function Safe\json_decode;
use function Safe\sprintf;

final class CreateController
{
    /**
     * @var EnvironmentRepository
     */
    private $repository;

    public function __construct(EnvironmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $raw = $this->getRequestContent($request);
        $json = $this->parseJsonRequest($raw);
        $environment = $this->createEnvironmentFromJson($json);
        $name = $environment->name();

        if ($this->repository->exists($name)) {
            throw new ConflictHttpException(sprintf('Environment with name "%s" already exists.', $name));
        }

        $this->repository->persist($environment);

        return new Response(sprintf('Created environment named "%s".', $name));
    }

    private function getRequestContent(Request $request): string
    {
        $raw = $request->getContent();
        if (!is_string($raw)) {
            throw new BadRequestHttpException('Invalid body, no content found.');
        }

        return $raw;
    }

    /**
     * @return array<mixed>
     */
    private function parseJsonRequest(string $raw): array
    {
        try {
            /** @var array<mixed> $parsed */
            $parsed = json_decode($raw, true);
        } catch (JsonException $jsonException) {
            throw new BadRequestHttpException($jsonException->getMessage(), $jsonException);
        }

        return $parsed;
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
