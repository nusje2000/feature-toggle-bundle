<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Http\Response;

use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;
use Nusje2000\FeatureToggleBundle\Feature\Feature;
use Nusje2000\FeatureToggleBundle\Feature\SimpleFeature;
use Nusje2000\FeatureToggleBundle\Feature\State;

final class FeatureMapper
{
    /**
     * @param array<mixed> $json
     */
    public static function map(array $json): Feature
    {
        $name = $json['name'] ?? null;
        if (!is_string($name)) {
            throw InvalidResponse::invalidKeyType('name', 'string', $name);
        }

        $enabled = $json['enabled'] ?? null;
        if (!is_bool($enabled)) {
            throw InvalidResponse::invalidKeyType('enabled', 'bool', $enabled);
        }

        $description = $json['description'] ?? null;
        if (!is_null($description) && !is_string($description)) {
            throw InvalidResponse::invalidKeyType('description', 'string|null', $description);
        }

        return new SimpleFeature($name, State::fromBoolean($enabled), $description);
    }
}
