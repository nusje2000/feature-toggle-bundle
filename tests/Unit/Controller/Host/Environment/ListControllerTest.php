<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Controller\Host\Environment\ListController;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use PHPUnit\Framework\TestCase;

final class ListControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(EnvironmentRepository::class);
        $repository->method('all')->willReturn([
            new SimpleEnvironment('env_1', [
                'env_1.host.com',
                'internal.host.com',
                '0.0.0.0',
            ], [
                new SimpleFeature('feature_1', State::ENABLED),
                new SimpleFeature('feature_2', State::DISABLED),
                new SimpleFeature('feature_3', State::DISABLED, 'FooBar'),
            ]),
            new SimpleEnvironment('env_2', [
                'env_2.host.com',
            ], [
                new SimpleFeature('feature_2', State::ENABLED),
                new SimpleFeature('feature_3', State::DISABLED),
                new SimpleFeature('feature_4', State::DISABLED, 'FooBar'),
            ]),
        ]);

        $controller = new ListController($repository);

        $response = $controller();

        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(
            [
                [
                    'name' => 'env_1',
                    'hosts' => [
                        'env_1.host.com',
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
                [
                    'name' => 'env_2',
                    'hosts' => [
                        'env_2.host.com',
                    ],
                    'features' => [
                        [
                            'name' => 'feature_2',
                            'enabled' => true,
                            'description' => null,
                        ],
                        [
                            'name' => 'feature_3',
                            'enabled' => false,
                            'description' => null,
                        ],
                        [
                            'name' => 'feature_4',
                            'enabled' => false,
                            'description' => 'FooBar',
                        ],
                    ],
                ],
            ],
            json_decode($content, true)
        );
    }
}
