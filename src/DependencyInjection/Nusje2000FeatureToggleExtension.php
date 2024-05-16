<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\DependencyInjection;

use Nusje2000\FeatureToggleBundle\AccessControl\RequestMatcherPattern;
use Nusje2000\FeatureToggleBundle\AccessControl\Requirement;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\FallbackEnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FallbackFeatureRepository;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\HostRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\IpsRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PortRequestMatcher;

final class Nusje2000FeatureToggleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/services'));
        $xmlLoader->load('cache.xml');
        $xmlLoader->load('controllers.xml');
        $xmlLoader->load('http.xml');
        $xmlLoader->load('subscribers.xml');

        /** @var array{
         *      logger: string|false|null,
         *      repository: array{
         *          enabled: bool,
         *          cache_adapter: string|null,
         *          service: array{
         *              enabled: bool,
         *              feature: string,
         *              environment: string
         *          },
         *          fallback: array{
         *              enabled: bool,
         *              feature: string|null,
         *              environment: string|null
         *          },
         *          static: bool|null,
         *          remote: array{
         *              enabled: bool,
         *              cache_store: string|null,
         *              host: string,
         *              scheme: string,
         *              base_path: string
         *          }
         *      },
         *      environment: array{
         *          enabled: bool,
         *          name: string,
         *          hosts: list<string>,
         *          features: array<array-key, array{
         *              enabled: bool,
         *              description: string|null
         *          }>,
         *          access_control: list<array{
         *              path: string|null,
         *              host: string|null,
         *              port: int|null,
         *              ips: list<string>,
         *              methods: list<string>,
         *              features: array<string, bool>
         *          }>
         *      }
         *  } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $this->configureLogger($container, $config['logger']);
        $this->configureRepository($container, $xmlLoader, $config['repository']);
        $this->configureEnvironment($container, $xmlLoader, $config['environment']);
    }

    /**
     * @param string|null|false $loggerConfig
     */
    private function configureLogger(ContainerBuilder $container, $loggerConfig): void
    {
        if (null === $loggerConfig && $container->has('logger')) {
            $loggerConfig = 'logger';
        }

        if (null === $loggerConfig || false === $loggerConfig) {
            $definition = new Definition(NullLogger::class);
            $definition->setPublic(true);
            $container->setDefinition('nusje2000_feature_toggle.logger', $definition);

            return;
        }

        $container->addAliases([
            'nusje2000_feature_toggle.logger' => new Alias($loggerConfig, true),
        ]);
    }

    /**
     * @param array{
     *     enabled: bool,
     *     name: string,
     *     hosts: list<string>,
     *     features: array<array-key, array{
     *          enabled: bool,
     *          description: string|null
     *     }>,
     *     access_control: list<array{
     *         path: string|null,
     *         host: string|null,
     *         port: int|null,
     *         ips: list<string>,
     *         methods: list<string>,
     *         features: array<string, bool>
     *     }>
     * } $config
     */
    private function configureEnvironment(ContainerBuilder $container, XmlFileLoader $xmlLoader, array $config): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('nusje2000_feature_toggle.environment_name', $config['name']);
        $container->setParameter('nusje2000_feature_toggle.hosts', $config['hosts']);
        $container->setParameter('nusje2000_feature_toggle.feature_defaults', $config['features']);

        $xmlLoader->load('environment.xml');

        if ($container->hasParameter('kernel.bundles')) {
            /** @var array<string, string> $bundles */
            $bundles = $container->getParameter('kernel.bundles');
            if (isset($bundles['TwigBundle'])) {
                $xmlLoader->load('twig.xml');
            }
        }

        foreach ($config['features'] as $name => $feature) {
            $container->getDefinition('nusje2000_feature_toggle.default_environment')->addMethodCall('addFeature', [
                new Definition(SimpleFeature::class, [
                    (string) $name,
                    State::fromBoolean($feature['enabled']),
                    $feature['description'],
                ]),
            ]);
        }

        $patterns = [];

        foreach ($config['access_control'] as $pattern) {
            $hash = ContainerBuilder::hash($pattern);

            $requestMatcherId = '.nusje2000_feature_toggle.access_control.request_matcher.' . $hash;
            $patterns[] = $patternId = '.nusje2000_feature_toggle.access_control.pattern.' . $hash;

            $requirements = [];
            foreach ($pattern['features'] as $name => $enabled) {
                $requirements[] = new Definition(Requirement::class, [
                    $name,
                    State::fromBoolean($enabled),
                ]);
            }

            $container->setDefinition($requestMatcherId, new Definition(
                ChainRequestMatcher::class,
                [
                    $this->getRequestMatchersFromPattern($pattern),
                ],
            ));

            $container->setDefinition($patternId, new Definition(
                RequestMatcherPattern::class,
                [
                    new Reference($requestMatcherId),
                    $requirements,
                ]
            ));
        }

        $map = $container->getDefinition('nusje2000_feature_toggle.access_control.access_map');
        foreach ($patterns as $pattern) {
            $map->addMethodCall('add', [new Reference($pattern)]);
        }

        if ($container->hasDefinition('nusje2000_feature_toggle.repository.environment.static')) {
            $container->getDefinition('nusje2000_feature_toggle.repository.environment.static')->setArguments([
                [new Reference('nusje2000_feature_toggle.default_environment')],
            ]);
        }
    }

    /**
     * @param array{
     *     enabled: bool|null,
     *     cache_adapter: string|null,
     *     service: array{
     *         enabled: bool,
     *         feature: string,
     *         environment: string
     *     },
     *     fallback: array{
     *         enabled: bool,
     *         feature: string|null,
     *         environment: string|null
     *     },
     *     static: bool|null,
     *     remote: array{
     *         enabled: bool,
     *         host: string,
     *         scheme: string,
     *         cache_store: string|null,
     *         base_path: string
     *     }
     * } $config
     */
    private function configureRepository(ContainerBuilder $container, XmlFileLoader $xmlLoader, array $config): void
    {
        $xmlLoader->load('repository/default_repository.xml');

        if (true === $config['remote']['enabled']) {
            $container->setParameter('nusje2000_feature_toggle.remote.host', $config['remote']['host']);
            $container->setParameter('nusje2000_feature_toggle.remote.scheme', $config['remote']['scheme']);
            $container->setParameter('nusje2000_feature_toggle.remote.base_path', $config['remote']['base_path']);

            $xmlLoader->load('repository/remote.xml');

            // This will be removed in 2.0
            if (null !== $config['remote']['cache_store']) {
                $container->setDefinition('nusje2000_feature_toggle.http_client.caching', new Definition(
                    CachingHttpClient::class,
                    [
                        new Reference('nusje2000_feature_toggle.http_client.native'),
                        new Reference($config['remote']['cache_store']),
                    ]
                ));

                $container->getDefinition('nusje2000_feature_toggle.http_client.scoping')->setArgument(
                    0,
                    new Reference('nusje2000_feature_toggle.http_client.caching')
                );
            }
        }

        if ($config['service']['enabled']) {
            $container->addAliases([
                'nusje2000_feature_toggle.repository.environment' => new Alias($config['service']['environment'], true),
                'nusje2000_feature_toggle.repository.feature' => new Alias($config['service']['feature'], true),
            ]);
        }

        $this->configureCache($container, $xmlLoader, $config['cache_adapter']);
        $this->configureFallback($container, $config['fallback']);
    }

    private function configureCache(ContainerBuilder $builder, XmlFileLoader $xmlLoader, ?string $adapterId): void
    {
        if (null === $adapterId) {
            return;
        }

        $builder->addAliases([
            'nusje2000_feature_toggle.cache_adapter' => new Alias($adapterId, true),
        ]);

        $xmlLoader->load('repository/caching.xml');
    }

    /**
     * @param ContainerBuilder $builder
     * @param array{
     *     enabled: bool,
     *     feature: string|null,
     *     environment: string|null
     * } $config
     */
    private function configureFallback(ContainerBuilder $builder, array $config): void
    {
        if (true !== $config['enabled']) {
            return;
        }

        $feature = $config['feature'];
        if ('static' === $feature) {
            $feature = 'nusje2000_feature_toggle.repository.feature.static';
        }

        if (null !== $feature) {
            $definition = new Definition(FallbackFeatureRepository::class, [
                new Reference('nusje2000_feature_toggle.logger'),
                new Reference('nusje2000_feature_toggle.repository.feature.fallback.inner'),
                new Reference($feature),
            ]);

            $definition->setPublic(true);
            $definition->setDecoratedService('nusje2000_feature_toggle.repository.feature');

            $builder->setDefinition('nusje2000_feature_toggle.repository.feature.fallback', $definition);
        }

        $environment = $config['environment'];
        if ('static' === $environment) {
            $environment = 'nusje2000_feature_toggle.repository.environment.static';
        }

        if (null !== $environment) {
            $definition = new Definition(FallbackEnvironmentRepository::class, [
                new Reference('nusje2000_feature_toggle.logger'),
                new Reference('nusje2000_feature_toggle.repository.environment.fallback.inner'),
                new Reference($environment),
            ]);

            $definition->setPublic(true);
            $definition->setDecoratedService('nusje2000_feature_toggle.repository.environment');

            $builder->setDefinition('nusje2000_feature_toggle.repository.environment.fallback', $definition);
        }
    }

    /**
     * @param array{
     *         path: string|null,
     *         host: string|null,
     *         port: int|null,
     *         ips: list<string>,
     *         methods: list<string>,
     *         features: array<string, bool>
     *     } $pattern
     *
     * @return list<Definition>
     */
    private function getRequestMatchersFromPattern(array $pattern): array
    {
        $matchers = [];
        if (is_string($pattern['path'])) {
            $matchers[] = new Definition(
                PathRequestMatcher::class,
                [
                    $pattern['path'],
                ],
            );
        }

        if (is_string($pattern['host'])) {
            $matchers[] = new Definition(
                HostRequestMatcher::class,
                [
                    $pattern['host'],
                ],
            );
        }

        if (is_int($pattern['port'])) {
            $matchers[] = new Definition(
                PortRequestMatcher::class,
                [
                    $pattern['port'],
                ],
            );
        }

        if (0 !== count($pattern['methods'])) {
            $matchers[] = new Definition(
                MethodRequestMatcher::class,
                [
                    $pattern['methods'],
                ],
            );
        }

        if (0 !== count($pattern['ips'])) {
            $matchers[] = new Definition(
                IpsRequestMatcher::class,
                [
                    $pattern['ips'],
                ],
            );
        }

        return $matchers;
    }
}
