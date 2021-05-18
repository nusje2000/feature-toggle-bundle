<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Repository;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Http\Response\EnvironmentMapper;
use Nusje2000\FeatureToggleBundle\Http\Response\FeatureMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function Safe\sprintf;

final class RemoteFeatureRepository implements FeatureRepository
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function all(string $environment): array
    {
        $response = $this->client->request(Request::METHOD_GET, '/' . $environment);
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        return EnvironmentMapper::map($response->toArray())->features();
    }

    public function find(string $environment, string $feature): Feature
    {
        $response = $this->client->request(Request::METHOD_GET, sprintf('/%s/%s', $environment, $feature));
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            throw UndefinedFeature::inEnvironment($environment, $feature);
        }

        return FeatureMapper::map($response->toArray());
    }

    public function exists(string $environment, string $feature): bool
    {
        $response = $this->client->request(Request::METHOD_HEAD, sprintf('/%s/%s', $environment, $feature));
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        return $response->getStatusCode() === Response::HTTP_OK;
    }

    public function add(string $environment, Feature $feature): void
    {
        $response = $this->client->request(Request::METHOD_POST, sprintf('/%s/create-feature', $environment), [
            'json' => [
                'name' => $feature->name(),
                'enabled' => $feature->state()->isEnabled(),
            ],
        ]);

        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND, Response::HTTP_CONFLICT]);

        if ($response->getStatusCode() === Response::HTTP_CONFLICT) {
            throw DuplicateFeature::inEnvironment($environment, $feature->name());
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            throw UndefinedEnvironment::create($environment);
        }
    }

    public function update(string $environment, Feature $feature): void
    {
        $response = $this->client->request(Request::METHOD_PUT, sprintf('/%s/%s', $environment, $feature->name()), [
            'json' => [
                'enabled' => $feature->state()->isEnabled(),
            ],
        ]);
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            throw UndefinedFeature::inEnvironment($environment, $feature->name());
        }
    }

    public function remove(string $environment, Feature $feature): void
    {
        $response = $this->client->request(Request::METHOD_DELETE, sprintf('/%s/%s', $environment, $feature->name()));
        $this->assertResponseStatus($response, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]);

        if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
            throw UndefinedFeature::inEnvironment($environment, $feature->name());
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
