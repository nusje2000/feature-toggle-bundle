<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Twig;

use Nusje2000\FeatureToggleBundle\FeatureToggle;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    /**
     * @var FeatureToggle
     */
    private $featureToggle;

    public function __construct(FeatureToggle $featureToggle)
    {
        $this->featureToggle = $featureToggle;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_feature_enabled', [$this->featureToggle, 'isEnabled']),
            new TwigFunction('is_feature_disabled', [$this->featureToggle, 'isDisabled']),
        ];
    }
}
