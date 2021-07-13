<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Subscriber;

use Nusje2000\FeatureToggleBundle\AccessControl\RequestValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class RequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestValidator
     */
    private $validator;

    public function __construct(RequestValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'validateAccess',
        ];
    }

    public function validateAccess(RequestEvent $event): void
    {
        $this->validator->validate($event->getRequest());
    }
}
