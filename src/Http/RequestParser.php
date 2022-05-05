<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class RequestParser
{
    /**
     * @return array<mixed>
     */
    public function json(Request $request): array
    {
        $raw = $this->getRequestContent($request);

        return $this->parseJsonRequest($raw);
    }

    private function getRequestContent(Request $request): string
    {
        return $request->getContent();
    }

    /**
     * @return array<mixed>
     */
    private function parseJsonRequest(string $raw): array
    {
        $parsed = json_decode($raw, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(json_last_error_msg(), null, json_last_error());
        }

        if (!is_array($parsed)) {
            throw new BadRequestHttpException('Could not decode json.');
        }

        return $parsed;
    }
}
