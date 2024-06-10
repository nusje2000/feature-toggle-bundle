<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\FeatureNotSupported;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Http\Response\EnvironmentMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RemoteEnvironmentRepository implements EnvironmentRepository
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $basePath,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $response = $this->client->request(Request::METHOD_GET, $this->basePath);

        $this->assertResponseStatus($response, [Response::HTTP_OK]);

        return array_map(static function (array $item) {
            return EnvironmentMapper::map($item);
        }, array_values($response->toArray()));
    }

    public function find(string $environment): Environment
    {
        $response = $this->client->request(Request::METHOD_GET, $this->basePath . '/' . $environment);
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            throw UndefinedEnvironment::create($environment);
        }

        return EnvironmentMapper::map($response->toArray());
    }

    public function exists(string $environment): bool
    {
        $response = $this->client->request(Request::METHOD_HEAD, $this->basePath . '/' . $environment);
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        return $response->getStatusCode() === Response::HTTP_OK;
    }

    public function add(Environment $environment): void
    {
        if (0 !== count($environment->features())) {
            throw new FeatureNotSupported('Cannot create an environment with preset features.');
        }

        $response = $this->client->request(Request::METHOD_POST, $this->basePath . '/create-environment', [
            'json' => [
                'name' => $environment->name(),
                'hosts' => $environment->hosts(),
            ],
        ]);

        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_CONFLICT]);

        if ($response->getStatusCode() === Response::HTTP_CONFLICT) {
            throw DuplicateEnvironment::create($environment->name());
        }
    }

    /**
     * @param list<int> $expected
     */
    private function assertResponseStatus(ResponseInterface $response, array $expected): void
    {
        if (!in_array($response->getStatusCode(), $expected, true)) {
            throw InvalidResponse::unexpectedStatus($response->getStatusCode(), $expected);
        }
    }
}
