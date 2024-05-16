<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Controller\Host\Feature\DeleteController;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use PHPUnit\Framework\TestCase;

final class DeleteControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = $this->createMock(FeatureRepository::class);
        $repository->method('find')->willReturn(new SimpleFeature('feature_1', State::ENABLED));
        $repository->expects(self::once())->method('remove')->with(
            'environment',
            new SimpleFeature('feature_1', State::ENABLED)
        );

        $controller = new DeleteController($repository);

        $controller('environment', 'feature_1');
    }
}
