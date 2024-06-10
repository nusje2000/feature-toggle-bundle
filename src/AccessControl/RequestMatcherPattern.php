<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final class RequestMatcherPattern implements Pattern
{
    /**
     * @param list<Requirement> $requirements
     */
    public function __construct(
        private readonly RequestMatcherInterface $matcher,
        private readonly array $requirements,
    ) {
    }

    public function matches(Request $request): bool
    {
        return $this->matcher->matches($request);
    }

    /**
     * @inheritDoc
     */
    public function requirements(): array
    {
        return $this->requirements;
    }
}
