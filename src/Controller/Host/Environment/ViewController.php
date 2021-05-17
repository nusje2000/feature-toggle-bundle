<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Environment;

use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Repository\EnvironmentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Safe\sprintf;

final class ViewController
{
    /**
     * @var EnvironmentRepository
     */
    private $repository;

    public function __construct(EnvironmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $name): Response
    {
        try {
            $environment = $this->repository->find($name);
        } catch (UndefinedEnvironment $environment) {
            throw new NotFoundHttpException(sprintf('No environment found named "%s".', $name), $environment);
        }

        return new JsonResponse([
            'name' => $environment->name(),
            'hosts' => $environment->hosts(),
            'features' => array_map(
                static function (Feature $feature) use ($environment) {
                    return [
                        'name' => $feature->name(),
                        'environment' => $environment->name(),
                        'enabled' => $feature->state()->isEnabled(),
                    ];
                },
                array_values($environment->features())
            ),
        ]);
    }
}
