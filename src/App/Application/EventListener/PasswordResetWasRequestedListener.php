<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\EventListener;

use LtcZelhem\App\Application\Command\SendPasswordResetNotification;
use LtcZelhem\App\Domain\Events\PasswordResetWasRequested;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class PasswordResetWasRequestedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(PasswordResetWasRequested $event): void
    {
        $this->messageBus->dispatch(
            new SendPasswordResetNotification(
                $event->emailAddress,
                $event->fullName,
                $event->passwordToken,
                $event->tokenExpiresAt,
                $event->locale,
            )
        );
    }
}