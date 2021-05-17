<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Controller\Host\Environment\CreateController;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CreateControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);
        $repository->method('exists')->willReturn(false);
        $repository->expects(self::once())->method('persist')->with(
            new SimpleEnvironment('some_env', ['some.host'], [])
        );

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "some_env", "host": "some.host"}');

        $controller($request);
    }

    public function testInvokeWithExistingEnvironment(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);
        $repository->method('exists')->willReturn(true);
        $repository->expects(self::never())->method('persist');

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "some_env", "host": "some.host"}');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Environment with name "some_env" already exists.');

        $controller($request);
    }

    public function testInvokeWithMissingName(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"host": "some.host"}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing/Invalid environment name, please provide a string value for the "name" key.');

        $controller($request);
    }

    public function testInvokeWithMissingHost(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "some_env"}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing/Invalid environment host, please provide a string value for the "host" key.');

        $controller($request);
    }
}
