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
    /**
     * @dataProvider invokeSupplier
     */
    public function testInvoke(SimpleFeature $initialFeature, SimpleFeature $updatedFeature, string $requestContent): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn($initialFeature);
        $repository->expects(self::once())->method('update')->with('environment', $updatedFeature);

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn($requestContent);

        $controller($request, 'environment', 'feature');
    }

    /**
     * @return iterable<string, array{0: SimpleFeature, 1: SimpleFeature, 2: string}>
     */
    public function invokeSupplier(): iterable
    {
        return [
            'enable_state' => [
                new SimpleFeature('feature_1', State::DISABLED, null),
                new SimpleFeature('feature_1', State::ENABLED, null),
                '{"enabled": true}',
            ],
            'disable_state' => [
                new SimpleFeature('feature_1', State::ENABLED, null),
                new SimpleFeature('feature_1', State::DISABLED, null),
                '{"enabled": false}',
            ],
            'only_update_description' => [
                new SimpleFeature('feature_1', State::DISABLED, null),
                new SimpleFeature('feature_1', State::DISABLED, 'fooBar'),
                '{"description": "fooBar"}',
            ],
            'update_state_and_description' => [
                new SimpleFeature('feature_1', State::DISABLED, null),
                new SimpleFeature('feature_1', State::ENABLED, 'fooBar'),
                '{"enabled": true, "description": "fooBar"}',
            ],
            'update_nothing' => [
                new SimpleFeature('feature_1', State::ENABLED, 'fooBar'),
                new SimpleFeature('feature_1', State::ENABLED, 'fooBar'),
                '{}',
            ],
        ];
    }

    public function testInvokeWithInvalidDescription(): void
    {
        $repository = $this->createMock(FeatureRepository::class);

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"enabled": true, "description": []}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid feature description, please provide a string or null value for the "description" key, or exclude the value from the json.');

        $controller($request, 'environment', 'feature');
    }

    public function testInvokeWithInvalidState(): void
    {
        $repository = $this->createMock(FeatureRepository::class);

        $controller = new UpdateController(new RequestParser(), $repository);

        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"enabled": "FooBar"}');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid feature state, please provide a boolean value for the "enabled" key.');

        $controller($request, 'environment', 'feature');
    }
}
