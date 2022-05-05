<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Http;

use Nusje2000\FeatureToggleBundle\Http\RequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class RequestParserTest extends TestCase
{
    public function testJson(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('{"key": "value"}');

        self::assertSame(['key' => 'value'], (new RequestParser())->json($request));
    }

    public function testJsonWithInvalidJson(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getContent')->willReturn('invalid json');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Syntax error');

        (new RequestParser())->json($request);
    }
}
