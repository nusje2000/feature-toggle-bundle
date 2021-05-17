<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Controller\Host\Feature\CreateController;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CreateControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('exists')->willReturn(false);
        $repository->expects(self::once())->method('persist')->with(
            'environment',
            new SimpleFeature('feature_1', State::ENABLED())
        );

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "feature_1", "enabled": true}');

        $controller($request, 'environment');
    }

    public function testInvokeWithExistingFeature(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('exists')->willReturn(true);
        $repository->expects(self::never())->method('persist');

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "feature_1", "enabled": true}');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Feature named "feature_1" in environment "environment" already exists.');

        $controller($request, 'environment');
    }

    public function testInvokeWithUndefinedEnvironment(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('exists')->willThrowException(UndefinedEnvironment::create('environment'));

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "feature_1", "enabled": true}');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Environment "environment" is not defind.');

        $controller($request, 'environment');
    }

    public function testInvokeWithMissingName(): void
    {
        $repository = $this->createMock(FeatureRepository::class);

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"enabled": true}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing/Invalid environment name, please provide a string value for the "name" key.');

        $controller($request, 'environment');
    }

    public function testInvokeWithMissingState(): void
    {
        $repository = $this->createMock(FeatureRepository::class);

        $controller = new CreateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "some_env"}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing/Invalid feature state, please provide a boolean value for the "enabled" key.');

        $controller($request, 'environment');
    }
}
