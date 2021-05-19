<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use Nusje2000\FeatureToggleBundle\Console\UpdateCommand;
use Nusje2000\FeatureToggleBundle\Controller\Host\Environment;
use Nusje2000\FeatureToggleBundle\Controller\Host\Feature;
use Nusje2000\FeatureToggleBundle\DependencyInjection\Nusje2000FeatureToggleExtension;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\ArrayEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\ArrayFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\RemoteEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\RemoteFeatureRepository;
use Nusje2000\FeatureToggleBundle\Subscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

use function Safe\sprintf;

final class Nusje2000FeatureToggleExtensionTest extends TestCase
{
    public function testLoadWithDefaultConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.subscriber.exception', ExceptionSubscriber::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.create', Environment\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.view', Environment\ViewController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.list', Environment\ListController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.create', Feature\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.update', Feature\UpdateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.delete', Feature\DeleteController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.view', Feature\ViewController::class, false);
    }

    public function testLoadWithDefinedEnvironmentConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'environment' => [
                    'name' => 'some_environment',
                    'hosts' => ['localhost'],
                    'features' => [
                        'enabled_feature' => true,
                        'disabled_feature' => false,
                    ],
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.console.update_command', UpdateCommand::class, true);

        self::assertSame('some_environment', $container->getParameter('nusje2000_feature_toggle.environment_name'));

        self::assertEquals(['localhost'], $container->getParameter('nusje2000_feature_toggle.hosts'));

        self::assertEquals([
            'enabled_feature' => true,
            'disabled_feature' => false,
        ], $container->getParameter('nusje2000_feature_toggle.feature_defaults'));

        /** @var EnvironmentRepository $repository */
        $repository = $container->get('nusje2000_feature_toggle.repository.environment');

        $environments = $repository->all();
        self::assertEquals([
            new SimpleEnvironment(
                'some_environment',
                ['localhost'],
                [
                    new SimpleFeature('enabled_feature', State::ENABLED()),
                    new SimpleFeature('disabled_feature', State::DISABLED()),
                ]
            ),
        ], $environments);

        self::assertEquals(new SimpleEnvironment(
            'some_environment',
            ['localhost'],
            [
                new SimpleFeature('enabled_feature', State::ENABLED()),
                new SimpleFeature('disabled_feature', State::DISABLED()),
            ]
        ), $container->get('nusje2000_feature_toggle.default_environment'));
    }

    public function testLoadWithRemoteConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'remote' => [
                        'host' => 'some.host',
                    ],
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', RemoteEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', RemoteFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.http_client', ScopingHttpClient::class, false);
    }

    public function testLoadWithRemoteWithCacheConfiguration(): void
    {
        $container = new ContainerBuilder();

        $environmentRepository = $this->createStub(StoreInterface::class);
        $container->set('store_service_id', $environmentRepository);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'remote' => [
                        'host' => 'some.host',
                        'cache_store' => 'store_service_id',
                    ],
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', RemoteEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', RemoteFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.http_client', CachingHttpClient::class, false);
    }

    public function testLoadWithServiceConfiguration(): void
    {
        $container = new ContainerBuilder();

        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $container->set('environment_repository_id', $environmentRepository);

        $featureRepository = $this->createStub(FeatureRepository::class);
        $container->set('feature_repository_id', $featureRepository);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'service' => [
                        'feature' => 'feature_repository_id',
                        'environment' => 'environment_repository_id',
                    ],
                ],
            ],
        ], $container);

        self::assertSame($environmentRepository, $container->get('nusje2000_feature_toggle.repository.environment'));
        self::assertSame($featureRepository, $container->get('nusje2000_feature_toggle.repository.feature'));
    }

    public function testLoadWithStaticConfiguration(): void
    {
        $container = new ContainerBuilder();

        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $container->set('environment_repository_id', $environmentRepository);

        $featureRepository = $this->createStub(FeatureRepository::class);
        $container->set('feature_repository_id', $featureRepository);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'static' => true,
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);
    }

    public function testLoadWithMultipleRepositories(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $this->expectException(InvalidArgumentException::class);
        $this->expectDeprecationMessage('Only one of ["service", "static", "remote"] can be configured');
        $extension->load([
            [
                'repository' => [
                    'static' => true,
                    'service' => 'some_service',
                ],
            ],
        ], $container);
    }

    /**
     * @param class-string $class
     */
    private function assertDefinition(ContainerBuilder $builder, string $id, string $class, bool $public): void
    {
        self::assertTrue($builder->has($id), sprintf('Definition for "%s" does not exist.', $id));

        if ($builder->hasAlias($id)) {
            $id = (string) $builder->getAlias($id);
        }

        self::assertInstanceOf($class, $builder->get($id));

        $definition = $builder->getDefinition($id);
        self::assertSame($public, $definition->isPublic());
    }
}
