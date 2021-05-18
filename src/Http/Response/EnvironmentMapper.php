<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Http\Response;

use Nusje2000\FeatureToggleBundle\Environment\Environment;
use Nusje2000\FeatureToggleBundle\Environment\SimpleEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\Http\InvalidResponse;

final class EnvironmentMapper
{
    /**
     * @param array<mixed> $json
     */
    public static function map(array $json): Environment
    {
        $name = $json['name'] ?? null;
        if (!is_string($name)) {
            throw InvalidResponse::invalidKeyType('name', 'string', $name);
        }

        $environment = SimpleEnvironment::empty($name);

        $hosts = $json['hosts'] ?? null;
        if (!is_array($hosts)) {
            throw InvalidResponse::invalidKeyType('hosts', 'array', $hosts);
        }

        foreach ($hosts as $key => $host) {
            if (!is_string($host)) {
                throw InvalidResponse::invalidKeyType('hosts.' . $key, 'string', $hosts);
            }

            $environment->addHost($host);
        }

        $features = $json['features'] ?? null;
        if (!is_array($features)) {
            throw InvalidResponse::invalidKeyType('features', 'array', $features);
        }

        foreach ($features as $key => $feature) {
            if (!is_array($feature)) {
                throw InvalidResponse::invalidKeyType('features.' . $key, 'array', $hosts);
            }

            $environment->addFeature(FeatureMapper::map($feature));
        }

        return $environment;
    }
}
