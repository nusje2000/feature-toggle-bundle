<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Subscriber;

use Nusje2000\FeatureToggleBundle\AccessControl\RequestValidator;
use Nusje2000\FeatureToggleBundle\Subscriber\RequestSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class RequestSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            RequestEvent::class => 'validateAccess',
        ], RequestSubscriber::getSubscribedEvents());
    }

    public function testValidateAccess(): void
    {
        $request = new Request();

        $validator = $this->createMock(RequestValidator::class);
        $validator->expects(self::once())->method('validate')->with($request);

        $subscriber = new RequestSubscriber($validator, 'error_controller');
        $subscriber->validateAccess(new RequestEvent($this->createStub(KernelInterface::class), $request, null));
    }
}
