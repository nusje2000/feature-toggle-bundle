<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle;

use Nusje2000\FeatureToggleBundle\Feature\Feature;

interface FeatureToggle
{
    public function get(string $feature): Feature;

    public function exists(string $feature): bool;

    public function isEnabled(string $feature): bool;

    public function isDisabled(string $feature): bool;

    public function assertDefined(string $feature): void;

    public function assertEnabled(string $feature): void;

    public function assertDisabled(string $feature): void;
}
