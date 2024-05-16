<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Repository;

use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\FeatureNotSupported;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\RemoteEnvironmentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RemoteEnvironmentRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK, [
            [
                'name' => 'environment_1',
                'hosts' => ['host'],
                'features' => [
                    ['name' => 'feature_1', 'enabled' => true],
                ],
            ],
        ]);

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path')->willReturn($response);

        $environments = $repository->all();

        self::assertEquals([new SimpleEnvironment('environment_1', ['host'], [new SimpleFeature('feature_1', State::ENABLED)])], $environments);
    }

    public function testAllWithInvalidStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK]));
        $repository->all();
    }

    public function testFind(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK, [
            'name' => 'environment_1',
            'hosts' => ['host'],
            'features' => [
                ['name' => 'feature_1', 'enabled' => true],
            ],
        ]);

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path/environment_1')->willReturn($response);

        $environment = $repository->find('environment_1');
        self::assertEquals(new SimpleEnvironment('environment_1', ['host'], [new SimpleFeature('feature_1', State::ENABLED)]), $environment);
    }

    public function testFindUndefinedEnvironment(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path/undefined_environment')->willReturn(
            $this->createResponse(Response::HTTP_NOT_FOUND)
        );

        $this->expectExceptionObject(UndefinedEnvironment::create('undefined_environment'));
        $repository->find('undefined_environment');
    }

    public function testFindWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->find('new-environment');
    }

    public function testExists(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_HEAD, '/base-path/undefined_environment')->willReturn(
            $this->createResponse(Response::HTTP_OK)
        );

        self::assertTrue($repository->exists('undefined_environment'));
    }

    public function testExistsUndefinedEnvironment(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_HEAD, '/base-path/undefined_environment')->willReturn(
            $this->createResponse(Response::HTTP_NOT_FOUND)
        );

        self::assertFalse($repository->exists('undefined_environment'));
    }

    public function testExistsWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->exists('new-environment');
    }

    public function testAdd(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_POST, '/base-path/create-environment', [
            'json' => [
                'name' => 'new-environment',
                'hosts' => ['some_host', 'some_other_host'],
            ],
        ])->willReturn(
            $this->createResponse(Response::HTTP_OK)
        );

        $repository->add(new SimpleEnvironment('new-environment', ['some_host', 'some_other_host'], []));
    }

    public function testAddWithDefinedFeatures(): void
    {
        $repository = new RemoteEnvironmentRepository(
            $this->createStub(HttpClientInterface::class),
            '/base-path'
        );

        $this->expectExceptionObject(new FeatureNotSupported('Cannot create an environment with preset features.'));
        $repository->add(new SimpleEnvironment('new-environment', [], [new SimpleFeature('feature', State::ENABLED)]));
    }

    public function testAddExistingEnvironment(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->with(Request::METHOD_POST, '/base-path/create-environment', [
            'json' => [
                'name' => 'existing-environment',
                'hosts' => [],
            ],
        ])->willReturn(
            $this->createResponse(Response::HTTP_CONFLICT)
        );

        $this->expectExceptionObject(DuplicateEnvironment::create('existing-environment'));
        $repository->add(new SimpleEnvironment('existing-environment', [], []));
    }

    public function testAddWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteEnvironmentRepository($client, '/base-path');

        $client->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_CONFLICT]));
        $repository->add(SimpleEnvironment::empty('existing-environment'));
    }

    /**
     * @param array<mixed> $body
     */
    private function createResponse(int $status, array $body = []): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($body);
        $response->method('getStatusCode')->willReturn($status);

        return $response;
    }
}
