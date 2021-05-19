<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Controller\Client;

use Nusje2000\FeatureToggleBundle\Cache\Invalidator;
use Symfony\Component\HttpFoundation\Response;

final class InvalidateCacheController
{
    /**
     * @var Invalidator
     */
    private $invalidator;

    public function __construct(Invalidator $invalidator)
    {
        $this->invalidator = $invalidator;
    }

    public function __invoke(): Response
    {
        $this->invalidator->invalidate();

        return new Response('Cache has been invalidated.', Response::HTTP_OK);
    }
}
