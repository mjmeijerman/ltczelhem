<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyEventSubscriber;

use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $defaultLocale,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $user = $this->tokenStorage->getToken()?->getUser();
        if ($user instanceof UserCredentials) {
            $request->setLocale($user->locale()->value);

            return;
        }

        if ($request->hasPreviousSession()) {
            $locale = $request->getSession()->get('_locale');
            if ($locale && in_array($locale, Locale::allAsStringArray(), true)) {
                $request->setLocale($locale);

                return;
            }
        }

        if ($request->getLocale() === 'nl' || $request->getLocale() === 'en') {
            return;
        }

        $request->setLocale($this->defaultLocale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
