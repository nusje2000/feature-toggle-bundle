<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Controller\Host\Environment\ViewController;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use PHPUnit\Framework\TestCase;

final class ViewControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);
        $repository->method('find')->willReturn(new SimpleEnvironment('some_env', [
            'www.host.com',
            'internal.host.com',
            '0.0.0.0',
        ], [
            new SimpleFeature('feature_1', State::ENABLED),
            new SimpleFeature('feature_2', State::DISABLED),
            new SimpleFeature('feature_3', State::DISABLED, 'FooBar'),
        ]));

        $controller = new ViewController($repository);

        $response = $controller('some_env');

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(
            [
                'name' => 'some_env',
                'hosts' => [
                    'www.host.com',
                    'internal.host.com',
                    '0.0.0.0',
                ],
                'features' => [
                    [
                        'name' => 'feature_1',
                        'enabled' => true,
                        'description' => null,
                    ],
                    [
                        'name' => 'feature_2',
                        'enabled' => false,
                        'description' => null,
                    ],
                    [
                        'name' => 'feature_3',
                        'enabled' => false,
                        'description' => 'FooBar',
                    ],
                ],
            ],
            json_decode($content, true)
        );
    }
}
