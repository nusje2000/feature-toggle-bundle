<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use InvalidArgumentException;
use Nusje2000\FeatureToggleBundle\AccessControl\AccessMap;
use Nusje2000\FeatureToggleBundle\AccessControl\AccessMapRequestValidator;
use Nusje2000\FeatureToggleBundle\AccessControl\RequestMatcherPattern;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Cache\NullInvalidator;
use Nusje2000\FeatureToggleBundle\Console\CleanupCommand;
use Nusje2000\FeatureToggleBundle\Console\UpdateCommand;
use Nusje2000\FeatureToggleBundle\Controller\Host\Environment;
use Nusje2000\FeatureToggleBundle\Controller\Host\Feature;
use Nusje2000\FeatureToggleBundle\Decorator\CachingEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Decorator\CachingFeatureRepository;
use Nusje2000\FeatureToggleBundle\DependencyInjection\Nusje2000FeatureToggleExtension;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\FeatureToggle;
use Nusje2000\FeatureToggleBundle\Repository\ArrayEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\ArrayFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FallbackEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FallbackFeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\Repository\RemoteEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\RemoteFeatureRepository;
use Nusje2000\FeatureToggleBundle\RepositoryFeatureToggle;
use Nusje2000\FeatureToggleBundle\Subscriber\ExceptionSubscriber;
use Nusje2000\FeatureToggleBundle\Subscriber\RequestSubscriber;
use Nusje2000\FeatureToggleBundle\Twig\TwigExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IpsRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

final class Nusje2000FeatureToggleExtensionTest extends TestCase
{
    public function testLoadWithDefaultConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.cache.invalidator', NullInvalidator::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.subscriber.exception', ExceptionSubscriber::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);
        $this->assertDefinition($container, EnvironmentRepository::class, ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, FeatureRepository::class, ArrayFeatureRepository::class, true);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.create', Environment\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.view', Environment\ViewController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.list', Environment\ListController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.create', Feature\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.update', Feature\UpdateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.delete', Feature\DeleteController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.view', Feature\ViewController::class, false);
    }

    public function testLoadWithoutLoggerConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.logger', NullLogger::class, true);
    }

    public function testLoadWithLoggerConfiguration(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $container = new ContainerBuilder();
        $container->set('existing_logger', $logger);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([['logger' => 'existing_logger']], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.logger', get_class($logger), true);
    }

    public function testLoadWithoutLoggerConfigurationAndPrecentLoggerService(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $container = new ContainerBuilder();
        $container->set('logger', $logger);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.logger', get_class($logger), true);
    }

    public function testLoadWithDisabledLoggerConfiguration(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            ['logger' => false],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.logger', NullLogger::class, true);
    }

    public function testLoadWithDefinedEnvironmentConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.error_controller', null);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'environment' => [
                    'name' => 'some_environment',
                    'hosts' => ['localhost'],
                    'features' => [
                        'enabled_feature' => true,
                        'disabled_feature' => false,
                        'overwriten_feature' => false,
                        'descriptive_feature' => [
                            'enabled' => true,
                            'description' => 'fooBar',
                        ],
                    ],
                ],
            ],
            [
                'environment' => [
                    'features' => [
                        'overwriten_feature' => true,
                    ],
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.access_control.access_map', AccessMap::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.access_control.request_validator', AccessMapRequestValidator::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.subscriber.request', RequestSubscriber::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.console.update_command', UpdateCommand::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.console.cleanup_command', CleanupCommand::class, true);

        self::assertSame('some_environment', $container->getParameter('nusje2000_feature_toggle.environment_name'));

        self::assertEquals(['localhost'], $container->getParameter('nusje2000_feature_toggle.hosts'));

        self::assertEquals([
            'enabled_feature' => ['enabled' => true, 'description' => null],
            'disabled_feature' => ['enabled' => false, 'description' => null],
            'overwriten_feature' => ['enabled' => true, 'description' => null],
            'descriptive_feature' => ['enabled' => true, 'description' => 'fooBar'],
        ], $container->getParameter('nusje2000_feature_toggle.feature_defaults'));

        /** @var EnvironmentRepository $repository */
        $repository = $container->get('nusje2000_feature_toggle.repository.environment');

        $environments = $repository->all();
        self::assertEquals([
            new SimpleEnvironment(
                'some_environment',
                ['localhost'],
                [
                    new SimpleFeature('enabled_feature', State::ENABLED),
                    new SimpleFeature('disabled_feature', State::DISABLED),
                    new SimpleFeature('overwriten_feature', State::ENABLED),
                    new SimpleFeature('descriptive_feature', State::ENABLED, 'fooBar'),
                ]
            ),
        ], $environments);

        self::assertEquals(new SimpleEnvironment(
            'some_environment',
            ['localhost'],
            [
                new SimpleFeature('enabled_feature', State::ENABLED),
                new SimpleFeature('disabled_feature', State::DISABLED),
                new SimpleFeature('overwriten_feature', State::ENABLED),
                new SimpleFeature('descriptive_feature', State::ENABLED, 'fooBar'),
            ]
        ), $container->get('nusje2000_feature_toggle.default_environment'));

        $this->assertDefinition($container, FeatureToggle::class, RepositoryFeatureToggle::class, true);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', ArrayFeatureRepository::class, true);
        $this->assertDefinition($container, EnvironmentRepository::class, ArrayEnvironmentRepository::class, true);
        $this->assertDefinition($container, FeatureRepository::class, ArrayFeatureRepository::class, true);
    }

    public function testLoadWithAccessControl(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', ['TwigBundle' => []]);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'environment' => [
                    'name' => 'some_environment',
                    'hosts' => ['localhost'],
                    'features' => [],
                    'access_control' => [
                        ['path' => '^/feature-1-protected', 'ips' => ['127.0.0.1'], 'port' => 8080, 'features' => ['feature_1' => true]],
                        ['path' => '^/feature-2-protected', 'ips' => ['127.0.0.1'], 'features' => ['feature_2' => true]],
                        ['path' => '^/feature-3-protected', 'host' => 'symfony\.com$', 'features' => ['feature_3' => false]],
                        ['path' => '^/feature-4-and-5-protected', 'methods' => ['POST', 'PUT'], 'features' => ['feature_4' => false, 'feature_5' => true]],
                    ],
                ],
            ],
        ], $container);

        $expectedMap = new AccessMap();
        $expectedMap->add(new RequestMatcherPattern(
            new ChainRequestMatcher([
                new PathRequestMatcher('^/feature-1-protected'),
                new PortRequestMatcher(8080),
                new IpsRequestMatcher(['127.0.0.1']),
            ]),
            [new Requirement('feature_1', State::ENABLED)]
        ));
        $expectedMap->add(new RequestMatcherPattern(
            new ChainRequestMatcher([
                new PathRequestMatcher('^/feature-2-protected'),
                new IpsRequestMatcher(['127.0.0.1']),
            ]),
            [new Requirement('feature_2', State::ENABLED)]
        ));
        $expectedMap->add(new RequestMatcherPattern(
            new ChainRequestMatcher([
                new PathRequestMatcher('^/feature-3-protected'),
                new HostRequestMatcher('symfony\.com$'),
            ]),
            [new Requirement('feature_3', State::DISABLED)]
        ));
        $expectedMap->add(new RequestMatcherPattern(
            new ChainRequestMatcher([
                new PathRequestMatcher('^/feature-4-and-5-protected'),
                new MethodRequestMatcher(['POST', 'PUT']),
            ]),
            [new Requirement('feature_4', State::DISABLED), new Requirement('feature_5', State::ENABLED)]
        ));

        self::assertEquals($container->get('nusje2000_feature_toggle.access_control.access_map'), $expectedMap);
    }

    public function testLoadWithTwigBundle(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', ['TwigBundle' => []]);

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

        $this->assertDefinition($container, 'nusje2000_feature_toggle.twig_extension', TwigExtension::class, false);
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

        self::assertSame('some.host', $container->getParameter('nusje2000_feature_toggle.remote.host'));
        self::assertSame('https', $container->getParameter('nusje2000_feature_toggle.remote.scheme'));
        self::assertSame('/api/feature-toggle', $container->getParameter('nusje2000_feature_toggle.remote.base_path'));

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', RemoteEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', RemoteFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.http_client', ScopingHttpClient::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', RemoteEnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', RemoteFeatureRepository::class, true);
        $this->assertDefinition($container, EnvironmentRepository::class, RemoteEnvironmentRepository::class, true);
        $this->assertDefinition($container, FeatureRepository::class, RemoteFeatureRepository::class, true);
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
        $this->assertDefinition($container, 'nusje2000_feature_toggle.http_client', ScopingHttpClient::class, false);
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

    public function testLoadWithFallbackConfiguration(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DecoratorServicePass());

        $environmentRepository = $this->createStub(EnvironmentRepository::class);
        $container->set('fallback_environment_repository', $environmentRepository);

        $featureRepository = $this->createStub(FeatureRepository::class);
        $container->set('fallback_feature_repository', $featureRepository);

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'fallback' => [
                        'environment' => 'fallback_environment_repository',
                        'feature' => 'fallback_feature_repository',
                    ],
                ],
            ],
        ], $container);

        $definition = $container->getDefinition('nusje2000_feature_toggle.repository.environment.fallback');
        self::assertSame(['nusje2000_feature_toggle.repository.environment', null, 0], $definition->getDecoratedService());

        $definition = $container->getDefinition('nusje2000_feature_toggle.repository.feature.fallback');
        self::assertSame(['nusje2000_feature_toggle.repository.feature', null, 0], $definition->getDecoratedService());

        $container->compile();

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', FallbackFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', FallbackEnvironmentRepository::class, true);
    }

    public function testLoadWithStaticFallback(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DecoratorServicePass());

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'fallback' => [
                        'environment' => 'static',
                        'feature' => 'static',
                    ],
                ],
            ],
        ], $container);

        $definition = $container->getDefinition('nusje2000_feature_toggle.repository.environment.fallback');
        self::assertSame(['nusje2000_feature_toggle.repository.environment', null, 0], $definition->getDecoratedService());
        self::assertEquals(new Reference('nusje2000_feature_toggle.repository.environment.fallback.inner'), $definition->getArgument(1));
        self::assertEquals(new Reference('nusje2000_feature_toggle.repository.environment.static'), $definition->getArgument(2));

        $definition = $container->getDefinition('nusje2000_feature_toggle.repository.feature.fallback');
        self::assertSame(['nusje2000_feature_toggle.repository.feature', null, 0], $definition->getDecoratedService());
        self::assertEquals(new Reference('nusje2000_feature_toggle.repository.feature.fallback.inner'), $definition->getArgument(1));
        self::assertEquals(new Reference('nusje2000_feature_toggle.repository.feature.static'), $definition->getArgument(2));

        $container->compile();

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', FallbackFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', FallbackEnvironmentRepository::class, true);
    }

    public function testLoadWithCache(): void
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new DecoratorServicePass());
        $container->setDefinition('some_cache_adapter', new Definition(ArrayAdapter::class));

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([
            [
                'repository' => [
                    'cache_adapter' => 'some_cache_adapter',
                ],
            ],
        ], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.cache_adapter', ArrayAdapter::class, true);

        $container->compile();

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', CachingFeatureRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', CachingEnvironmentRepository::class, true);
    }

    public function testLoadWithMultipleRepositories(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of ["service", "static", "remote"] can be configured');
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

        if ($builder->hasDefinition($id)) {
            self::assertSame($public, $builder->getDefinition($id)->isPublic(), sprintf('Visibility of %s does not match expected.', $id));
        }

        if ($builder->hasAlias($id)) {
            self::assertSame($public, $builder->getAlias($id)->isPublic(), sprintf('Visibility of %s does not match expected.', $id));
            $id = (string) $builder->getAlias($id);
        }

        self::assertInstanceOf($class, $builder->get($id));
    }
}
