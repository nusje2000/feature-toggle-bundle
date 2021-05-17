<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Http;

use Safe\Exceptions\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function Safe\json_decode;

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
        $raw = $request->getContent();
        if (!is_string($raw)) {
            throw new BadRequestHttpException('Invalid body, no content found.');
        }

        return $raw;
    }

    /**
     * @return array<mixed>
     */
    private function parseJsonRequest(string $raw): array
    {
        try {
            /** @var array<mixed> $parsed */
            $parsed = json_decode($raw, true);
        } catch (JsonException $jsonException) {
            throw new BadRequestHttpException($jsonException->getMessage(), $jsonException);
        }

        return $parsed;
    }
}
