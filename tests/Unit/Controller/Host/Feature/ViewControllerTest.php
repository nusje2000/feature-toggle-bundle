<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Controller\Host\Feature\ViewController;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Safe\json_decode;

final class ViewControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::ENABLED()));

        $controller = new ViewController($repository);

        $response = $controller('some_env', 'feature_1');

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(
            [
                'name' => 'feature_1',
                'environment' => 'some_env',
                'enabled' => true,
            ],
            json_decode($content, true)
        );
    }

    public function testInvokeWithNonExistingFeature(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willThrowException(UndefinedFeature::inEnvironment('some_env', 'feature_1'));

        $controller = new ViewController($repository);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No feature found named "feature_1" in environment "some_env".');

        $controller('some_env', 'feature_1');
    }

    public function testInvokeWithNonExistingEnvironment(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willThrowException(UndefinedEnvironment::create('some_env'));

        $controller = new ViewController($repository);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No environment found named "some_env".');

        $controller('some_env', 'feature_1');
    }
}
