<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Feature;

enum State: int
{
    case DISABLED = 0;
    case ENABLED = 1;

    public static function fromBoolean(bool $enabled): self
    {
        if ($enabled) {
            return self::ENABLED;
        }

        return self::DISABLED;
    }

    public function isEnabled(): bool
    {
        return self::ENABLED === $this;
    }

    public function isDisabled(): bool
    {
        return self::DISABLED === $this;
    }
}
