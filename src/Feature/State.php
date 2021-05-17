<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Feature;

use MyCLabs\Enum\Enum;

/**
 * @extends Enum<int>
 *
 * @psalm-immutable
 *
 * @method static self DISABLED()
 * @method static self ENABLED()
 */
final class State extends Enum
{
    public const DISABLED = 0;
    public const ENABLED = 1;

    public static function fromBoolean(bool $enabled): self
    {
        if ($enabled) {
            return self::ENABLED();
        }

        return self::DISABLED();
    }

    public function isEnabled(): bool
    {
        return $this->equals(self::ENABLED());
    }

    public function isDisabled(): bool
    {
        return $this->equals(self::DISABLED());
    }
}
