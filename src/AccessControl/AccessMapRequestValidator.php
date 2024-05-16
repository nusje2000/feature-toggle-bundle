<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Nusje2000\FeatureToggleBundle\Exception\AccessControl\UnmetRequirement;
use Nusje2000\FeatureToggleBundle\FeatureToggle;
use Symfony\Component\HttpFoundation\Request;

final class AccessMapRequestValidator implements RequestValidator
{
    /**
     * @var AccessMap
     */
    private $accessMap;

    /**
     * @var FeatureToggle
     */
    private $featureToggle;

    public function __construct(AccessMap $accessMap, FeatureToggle $featureToggle)
    {
        $this->accessMap = $accessMap;
        $this->featureToggle = $featureToggle;
    }

    public function validate(Request $request): void
    {
        foreach ($this->accessMap->requirements($request) as $requirement) {
            $this->assert($requirement);
        }
    }

    private function assert(Requirement $requirement): void
    {
        $feature = $this->featureToggle->get($requirement->feature());
        if ($requirement->state() !== $feature->state()) {
            throw UnmetRequirement::byFeature($feature, $requirement);
        }
    }
}
