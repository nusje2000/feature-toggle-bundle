<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Repository;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\RemoteFeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RemoteFeatureRepositoryTest extends TestCase
{
    public function testAll(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK, [
            'name' => 'environment',
            'hosts' => ['host'],
            'features' => [
                ['name' => 'feature', 'enabled' => true],
            ],
        ]);

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path/environment')->willReturn($response);

        $environment = $repository->all('environment');
        self::assertEquals(['feature' => new SimpleFeature('feature', State::ENABLED)], $environment);
    }

    public function testAllWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->all('environment');
    }

    public function testFind(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK, [
            'name' => 'feature',
            'enabled' => true,
        ]);

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path/environment/feature')->willReturn($response);

        self::assertEquals(new SimpleFeature('feature', State::ENABLED), $repository->find('environment', 'feature'));
    }

    public function testFindWithUndefinedFeature(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_NOT_FOUND);

        $client->expects(self::once())->method('request')->with(Request::METHOD_GET, '/base-path/environment/feature')->willReturn($response);

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('environment', 'feature'));
        $repository->find('environment', 'feature');
    }

    public function testFindWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->find('environment', 'feature');
    }

    public function testExists(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK);

        $client->expects(self::once())->method('request')->with(Request::METHOD_HEAD, '/base-path/environment/feature')->willReturn($response);

        self::assertTrue($repository->exists('environment', 'feature'));
    }

    public function testExistsWithUndefinedFeature(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_NOT_FOUND);

        $client->expects(self::once())->method('request')->with(Request::METHOD_HEAD, '/base-path/environment/feature')->willReturn($response);

        self::assertFalse($repository->exists('environment', 'feature'));
    }

    public function testExistsWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->exists('environment', 'feature');
    }

    /**
     * @param list<mixed> $expectedWith
     *
     * @dataProvider addProvider
     */
    public function testAdd(Feature $feature, array $expectedWith): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK);

        $client->expects(self::once())->method('request')->with(...$expectedWith)->willReturn($response);

        $repository->add('environment', $feature);
    }

    /**
     * @return iterable<string, array{0: Feature, 1: list<mixed>}>
     */
    public function addProvider(): iterable
    {
        return [
            'feature_without_description' => [
                new SimpleFeature('new-feature', State::DISABLED),
                [
                    Request::METHOD_POST,
                    '/base-path/environment/create-feature',
                    [
                        'json' => [
                            'name' => 'new-feature',
                            'enabled' => false,
                            'description' => null,
                        ],
                    ],
                ],
            ],
            'feature_with_description' => [
                new SimpleFeature('new-feature', State::DISABLED, 'FooBar'),
                [
                    Request::METHOD_POST,
                    '/base-path/environment/create-feature',
                    [
                        'json' => [
                            'name' => 'new-feature',
                            'enabled' => false,
                            'description' => 'FooBar',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testAddInUndefinedEnvironment(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_NOT_FOUND);

        $client->expects(self::once())->method('request')->willReturn($response);

        $this->expectExceptionObject(UndefinedEnvironment::create('environment'));
        $repository->add('environment', new SimpleFeature('new-feature', State::DISABLED));
    }

    public function testAddWithDefinedFeature(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_CONFLICT);

        $client->expects(self::once())->method('request')->willReturn($response);

        $this->expectExceptionObject(DuplicateFeature::inEnvironment('environment', 'existing-feature'));
        $repository->add('environment', new SimpleFeature('existing-feature', State::DISABLED));
    }

    public function testAddWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [
            Response::HTTP_OK,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_CONFLICT,
        ]));
        $repository->add('environment', new SimpleFeature('feature', State::ENABLED));
    }

    /**
     * @param list<mixed> $expectedWith
     *
     * @dataProvider updateProvider
     */
    public function testUpdate(Feature $feature, array $expectedWith): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK);

        $client->expects(self::once())->method('request')->with(...$expectedWith)->willReturn($response);

        $repository->update('environment', $feature);
    }

    /**
     * @return iterable<string, array{0: Feature, 1: list<mixed>}>
     */
    public function updateProvider(): iterable
    {
        return [
            'update_with_description' => [
                new SimpleFeature('existing-feature', State::ENABLED),
                [
                    Request::METHOD_PUT,
                    '/base-path/environment/existing-feature',
                    [
                        'json' => [
                            'enabled' => true,
                            'description' => null,
                        ],
                    ],
                ],
            ],
            'update_without_description' => [
                new SimpleFeature('existing-feature', State::ENABLED, 'FooBar'),
                [
                    Request::METHOD_PUT,
                    '/base-path/environment/existing-feature',
                    [
                        'json' => [
                            'enabled' => true,
                            'description' => 'FooBar',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testUpdateUndefinedFeature(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_NOT_FOUND);

        $client->expects(self::once())->method('request')->willReturn($response);

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('environment', 'existing-feature'));
        $repository->update('environment', new SimpleFeature('existing-feature', State::ENABLED));
    }

    public function testUpdateWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->update('environment', new SimpleFeature('feature', State::ENABLED));
    }

    public function testRemove(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_OK);

        $client->expects(self::once())->method('request')->with(Request::METHOD_DELETE, '/base-path/environment/existing-feature')->willReturn($response);

        $repository->remove('environment', new SimpleFeature('existing-feature', State::ENABLED));
    }

    public function testRemoveWithUnexpectedStatusCode(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $client->expects(self::once())->method('request')->willReturn(
            $this->createResponse(Response::HTTP_I_AM_A_TEAPOT)
        );

        $this->expectExceptionObject(InvalidResponse::unexpectedStatus(Response::HTTP_I_AM_A_TEAPOT, [Response::HTTP_OK, Response::HTTP_NOT_FOUND]));
        $repository->update('environment', new SimpleFeature('feature', State::ENABLED));
    }

    public function testRemoveUndefinedFeature(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $repository = new RemoteFeatureRepository($client, '/base-path');

        $response = $this->createResponse(Response::HTTP_NOT_FOUND);

        $client->expects(self::once())->method('request')->willReturn($response);

        $this->expectExceptionObject(UndefinedFeature::inEnvironment('environment', 'existing-feature'));
        $repository->remove('environment', new SimpleFeature('existing-feature', State::ENABLED));
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
