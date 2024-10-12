<?php

declare(strict_types=1);

namespace LtcZelhem\App\Application\Command;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final readonly class SendNewUserConfirmationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface&LocaleAwareInterface $translator,
        private RouterInterface $router,
        private string $applicationUrl,
        private string $sender,
    ) {
    }

    public function __invoke(SendNewUserConfirmation $command): void
    {
        $message = new TemplatedEmail();
        $this->translator->setLocale($command->locale->value);
        $message->subject($this->translator->trans('email.new_user.subject'))
            ->from($this->sender)
            ->to($command->emailAddress->toString())
            ->htmlTemplate('mails/new_user.html.twig')
            ->context(
                [
                    'fullName' => $command->fullName,
                    'setPasswordUrl' => $this->applicationUrl.$this->router->generate(
                        'setPasswordForm',
                        ['passwordToken' => $command->passwordToken->toString()]
                    ),
                    'tokenExpiresAt' => $command->tokenExpiresAt->format('d-m-Y H:i:s'),
                    'application_url' => $this->applicationUrl,
                    'newCredentialsUrl' => $this->applicationUrl.$this->router->generate('getNewCredentials'),
                ]
            );

        $this->mailer->send($message);
    }
}
