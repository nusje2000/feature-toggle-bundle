<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Host\Feature;

use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Repository\FeatureRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Safe\sprintf;

final class ViewController
{
    /**
     * @var FeatureRepository
     */
    private $repository;

    public function __construct(FeatureRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $environment, string $name): Response
    {
        try {
            $feature = $this->repository->find($environment, $name);
        } catch (UndefinedFeature $exception) {
            throw new NotFoundHttpException(sprintf('No feature found named "%s" in environment "%s".', $name, $environment), $exception);
        } catch (UndefinedEnvironment $exception) {
            throw new NotFoundHttpException(sprintf('No environment found named "%s".', $environment), $exception);
        }

        return new JsonResponse([
            'name' => $feature->name(),
            'environment' => $environment,
            'enabled' => $feature->state()->isEnabled(),
        ]);
    }
}
