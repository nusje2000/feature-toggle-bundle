<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Subscriber;

use Nusje2000\FeatureToggleBundle\AccessControl\RequestValidator;
use Nusje2000\FeatureToggleBundle\Exception\AccessControl\UnmetRequirement;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestValidator $validator,
        private readonly ?string $errorController = null,
    ) {
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
        /** @var string|null $targetController */
        $targetController = $event->getRequest()->attributes->get('_controller');
        if ($this->errorController === $targetController) {
            return;
        }

        try {
            $this->validator->validate($event->getRequest());
        } catch (UnmetRequirement $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }
    }
}
