<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Nusje2000\FeatureToggleBundle\Controller\Host\Environment;
use Nusje2000\FeatureToggleBundle\Controller\Host\Feature;
use Nusje2000\FeatureToggleBundle\DependencyInjection\Nusje2000FeatureToggleExtension;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Nusje2000\FeatureToggleBundle\Subscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Nusje2000FeatureToggleExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new Nusje2000FeatureToggleExtension();
        $extension->load([], $container);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.subscriber.exception', ExceptionSubscriber::class, false);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.environment', EnvironmentRepository::class, true);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.repository.feature', FeatureRepository::class, true);

        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.create', Environment\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.view', Environment\ViewController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.environment.list', Environment\ListController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.create', Feature\CreateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.update', Feature\UpdateController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.delete', Feature\DeleteController::class, false);
        $this->assertDefinition($container, 'nusje2000_feature_toggle.controller.host.feature.view', Feature\ViewController::class, false);
    }

    /**
     * @param class-string $class
     */
    private function assertDefinition(ContainerBuilder $builder, string $id, string $class, bool $public): void
    {
        self::assertTrue($builder->has($id), 'Definition does not exist.');

        $definition = $builder->getDefinition($id);
        self::assertInstanceOf($class, $builder->get($id));

        self::assertSame($public, $definition->isPublic());
    }
}
