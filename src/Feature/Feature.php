<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Feature;

interface Feature
{
    public function name(): string;

    public function state(): State;
}
