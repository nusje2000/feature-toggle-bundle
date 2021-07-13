<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

interface Pattern
{
    public function matches(Request $request): bool;

    /**
     * @return list<Requirement>
     */
    public function requirements(): array;
}
