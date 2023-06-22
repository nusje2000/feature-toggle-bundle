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
    private RequestValidator $validator;

    private ?string $errorController;

    public function __construct(RequestValidator $validator, ?string $errorController = null)
    {
        $this->validator = $validator;
        $this->errorController = $errorController;
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
