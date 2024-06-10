<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\AccessControl;

use Symfony\Component\HttpFoundation\Request;

final class AccessMap
{
    /**
     * @var list<Pattern>
     */
    private array $patterns = [];

    public function add(Pattern $pattern): void
    {
        $this->patterns[] = $pattern;
    }

    /**
     * @return list<Requirement>
     */
    public function requirements(Request $request): array
    {
        foreach ($this->patterns as $pattern) {
            if ($pattern->matches($request)) {
                return $pattern->requirements();
            }
        }

        return [];
    }
}
