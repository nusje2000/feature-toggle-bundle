<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Tests\Unit\Subscriber;

use Nusje2000\FeatureToggleBundle\Exception\DuplicateEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\DuplicateFeature;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Nusje2000\FeatureToggleBundle\Subscriber\ExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class ExceptionSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            KernelEvents::EXCEPTION => 'mapException',
        ], ExceptionSubscriber::getSubscribedEvents());
    }

    public function testMapException(): void
    {
        $subscriber = new ExceptionSubscriber();

        $throwable = $this->createStub(Throwable::class);
        $event = $this->createEvent($throwable);
        $subscriber->mapException($event);
        self::assertSame($throwable, $event->getThrowable());

        $throwable = UndefinedEnvironment::create('some_env');
        $event = $this->createEvent($throwable);
        $subscriber->mapException($event);
        self::assertEquals(
            new HttpException(Response::HTTP_NOT_FOUND, $throwable->getMessage(), $throwable),
            $event->getThrowable()
        );

        $throwable = UndefinedFeature::inEnvironment('some_env', 'feature');
        $event = $this->createEvent($throwable);
        $subscriber->mapException($event);
        self::assertEquals(
            new HttpException(Response::HTTP_NOT_FOUND, $throwable->getMessage(), $throwable),
            $event->getThrowable()
        );

        $throwable = DuplicateEnvironment::create('some_env');
        $event = $this->createEvent($throwable);
        $subscriber->mapException($event);
        self::assertEquals(
            new HttpException(Response::HTTP_CONFLICT, $throwable->getMessage(), $throwable),
            $event->getThrowable()
        );

        $throwable = DuplicateFeature::inEnvironment('some_env', 'feature');
        $event = $this->createEvent($throwable);
        $subscriber->mapException($event);
        self::assertEquals(
            new HttpException(Response::HTTP_CONFLICT, $throwable->getMessage(), $throwable),
            $event->getThrowable()
        );
    }

    private function createEvent(Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(KernelInterface::class),
            $this->createStub(Request::class),
            1,
            $throwable
        );
    }
}
