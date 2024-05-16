<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Controller\Host\Feature\ViewController;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;

final class ViewControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::ENABLED));

        $controller = new ViewController($repository);

        $response = $controller('some_env', 'feature_1');

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(
            [
                'name' => 'feature_1',
                'enabled' => true,
                'description' => null,
            ],
            json_decode($content, true)
        );
    }

    public function testInvokeWithDescription(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::ENABLED, 'fooBar'));

        $controller = new ViewController($repository);

        $response = $controller('some_env', 'feature_1');

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(
            [
                'name' => 'feature_1',
                'enabled' => true,
                'description' => 'fooBar',
            ],
            json_decode($content, true)
        );
    }
}
