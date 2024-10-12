<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\EventListener;

use LtcZelhem\App\Application\Command\SendNewUserConfirmation;
use LtcZelhem\App\Domain\Events\NewUserWasCreated;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class NewUserWasCreatedListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(NewUserWasCreated $event): void
    {
        $this->messageBus->dispatch(
            new SendNewUserConfirmation(
                $event->emailAddress,
                $event->fullName,
                $event->passwordToken,
                $event->tokenExpiresAt,
                $event->locale
            )
        );
    }
}