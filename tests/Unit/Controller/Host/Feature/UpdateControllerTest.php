<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Controller\Host\Feature\UpdateController;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateControllerTest extends TestCase
{
    public function testInvokeWithEnableRequest(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::DISABLED()));
        $repository->expects(self::once())->method('persist')->with(
            'environment',
            new SimpleFeature('feature_1', State::ENABLED())
        );

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"enabled": true}');

        $controller($request, 'environment', 'feature');
    }

    public function testInvokeWithDisableRequest(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::ENABLED()));
        $repository->expects(self::once())->method('persist')->with(
            'environment',
            new SimpleFeature('feature_1', State::DISABLED())
        );

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"enabled": false}');

        $controller($request, 'environment', 'feature');
    }

    public function testInvokeWithMissingState(): void
    {
        $repository = $this->createMock(FeatureRepository::class);

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"name": "some_env"}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Missing/Invalid feature state, please provide a boolean value for the "enabled" key.');

        $controller($request, 'environment', 'feature');
    }
}
