<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nusje2000_feature_toggle');

        $root = $treeBuilder->getRootNode();

        $repository = $root->children()->arrayNode('repository')->canBeEnabled();

        $repository->beforeNormalization()->ifTrue(static function (array $config): bool {
            return count(array_diff(['remote', 'static', 'service'], array_keys($config))) < 2;
        })->thenInvalid('Only one of ["service", "static", "remote"] can be configured')->end();

        $repository->children()->booleanNode('static')->defaultNull();

        $repository->children()->scalarNode('cache_adapter')->defaultNull();

        $service = $repository->children()->arrayNode('fallback')->canBeEnabled()->children();
        $service->scalarNode('environment');
        $service->scalarNode('feature');

        $service = $repository->children()->arrayNode('service')->canBeEnabled()->children();
        $service->scalarNode('environment')->isRequired();
        $service->scalarNode('feature')->isRequired();

        $remote = $repository->children()->arrayNode('remote')->canBeEnabled()->children();
        $remote->scalarNode('host')->isRequired();
        $remote->scalarNode('scheme')->defaultValue('https');
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress TooFewArguments
         */
        $remote->scalarNode('cache_store')->setDeprecated('nusje2000/feature-toggle-bundle', '1.1.1')->defaultNull();
        $remote->scalarNode('base_path')->defaultValue('/api/feature-toggle');

        $environment = $root->children()->arrayNode('environment')->canBeEnabled()->children();
        $environment->scalarNode('name')->isRequired();
        $environment->arrayNode('hosts')->requiresAtLeastOneElement()->isRequired()->scalarPrototype();
        $features = $environment->arrayNode('features')->useAttributeAsKey('name')->arrayPrototype();
        $features->beforeNormalization()
            ->ifTrue(
                function (mixed $v) {
                    return is_bool($v);
                })->then(function (bool $v) {
                return ['enabled' => $v];
            });
        $features->children()->booleanNode('enabled')->isRequired();
        $features->children()->scalarNode('description')->defaultNull();

        $accessControl = $environment->arrayNode('access_control')->cannotBeOverwritten()->arrayPrototype()->children();
        $accessControl->scalarNode('path')->defaultNull();
        $accessControl->scalarNode('host')->defaultNull();
        $accessControl->integerNode('port')->defaultNull();
        $accessControl->arrayNode('ips')->scalarPrototype();
        $accessControl->arrayNode('methods')->scalarPrototype();
        $accessControl->arrayNode('features')->useAttributeAsKey('name')->booleanPrototype();

        $root->children()->scalarNode('logger')->defaultNull();

        return $treeBuilder;
    }
}
