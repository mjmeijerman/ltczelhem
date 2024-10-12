<?php

declare(strict_types=1);

namespace LtcZelhem\App\Application\Command;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class SendNewPasswordConfirmationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private RouterInterface $router,
        private string $applicationUrl,
        private string $sender,
    ) {
    }

    public function __invoke(SendNewPasswordConfirmation $command): void
    {
        $message = new TemplatedEmail();
        $message->subject($this->translator->trans('email.password_confirmation.subject'))
            ->from($this->sender)
            ->to($command->emailAddress->toString())
            ->htmlTemplate('mails/password_was_set.html.twig')
            ->context(
                [
                    'fullName' => $command->fullName,
                    'application_url' => $this->applicationUrl,
                    'newCredentialsUrl' => $this->applicationUrl.$this->router->generate('getNewCredentials'),
                ]
            );

        $this->mailer->send($message);
    }
}
