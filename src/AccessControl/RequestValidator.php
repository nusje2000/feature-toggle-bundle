<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

interface RequestValidator
{
    public function validate(Request $request): void;
}
