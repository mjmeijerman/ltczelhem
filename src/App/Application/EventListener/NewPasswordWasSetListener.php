<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\EventListener;

use LtcZelhem\App\Application\Command\SendNewPasswordConfirmation;
use LtcZelhem\App\Domain\Events\NewPasswordWasSet;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class NewPasswordWasSetListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function __invoke(NewPasswordWasSet $event): void
    {
        $this->messageBus->dispatch(
            new SendNewPasswordConfirmation(
                $event->emailAddress,
                $event->fullName,
            )
        );
    }
}