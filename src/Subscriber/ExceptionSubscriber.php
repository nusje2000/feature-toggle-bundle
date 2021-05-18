<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Subscriber;

use Nusje2000\FeatureToggleBundle\Exception\UndefinedEnvironment;
use Nusje2000\FeatureToggleBundle\Exception\UndefinedFeature;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

final class ExceptionSubscriber implements EventSubscriberInterface
{
    /** @var array<class-string<Throwable>, int> */
    private const MAPPING = [
        UndefinedFeature::class => Response::HTTP_NOT_FOUND,
        UndefinedEnvironment::class => Response::HTTP_NOT_FOUND,
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'mapException',
        ];
    }

    public function mapException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $class = get_class($throwable);

        /** @psalm-suppress InvalidArrayOffset */
        $status = self::MAPPING[$class] ?? null;

        if (null !== $status) {
            $event->setThrowable(
                new HttpException(
                    $status,
                    $throwable->getMessage(),
                    $throwable
                )
            );
        }
    }
}
