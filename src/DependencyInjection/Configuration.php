<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nusje2000_feature_toggle');

        /** @var ArrayNodeDefinition $root */
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
        $remote->scalarNode('cache_store')->setDeprecated(...$this->getDeprecationParameters('1.1.1'))->defaultNull();
        $remote->scalarNode('base_path')->defaultValue('/api/feature-toggle');

        $environment = $root->children()->arrayNode('environment')->canBeEnabled()->children();
        $environment->scalarNode('name')->isRequired();
        $environment->arrayNode('hosts')->requiresAtLeastOneElement()->isRequired()->scalarPrototype();
        $environment->arrayNode('features')->useAttributeAsKey('name')->booleanPrototype();

        $root->children()->scalarNode('logger')->defaultNull();

        return $treeBuilder;
    }

    /**
     * @return array{0: string|null}|array{0: string, 1: string, 2:string|null}
     */
    private function getDeprecationParameters(string $version, ?string $message = null): array
    {
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return [
                'nusje2000/feature-toggle-bundle',
                $version,
                $message,
            ];
        }

        return [$message];
    }
}
