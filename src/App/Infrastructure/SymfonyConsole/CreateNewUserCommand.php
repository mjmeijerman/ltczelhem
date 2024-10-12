<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyConsole;

use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Events\NewUserWasCreated;
use LtcZelhem\App\Domain\Security\PasswordToken;
use LtcZelhem\App\Domain\Security\Roles;
use LtcZelhem\App\Domain\Security\UserCredentialRepository;
use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Security\UserRole;
use LtcZelhem\App\Domain\Utilities\Clock;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand('app:create-new-user')]
final class CreateNewUserCommand extends Command
{
    public function __construct(
        private readonly UserCredentialRepository $userCredentialRepository,
        private readonly Clock $clock,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $questionHelper = new QuestionHelper();
        $output->writeln('Creating a new user');
        $output->writeln('===================');

        $emailAddressInput = $questionHelper->ask($input, $output, new Question('Email address: '));
        if (!is_string($emailAddressInput)) {
            throw new \InvalidArgumentException('Email address must be a string.');
        }
        $emailAddress = new EmailAddress(trim($emailAddressInput));

        $fullNameInput = $questionHelper->ask($input, $output, new Question('Full name: '));
        if (!is_string($fullNameInput)) {
            throw new \InvalidArgumentException('Full name must be a string.');
        }
        $fullName = trim($fullNameInput);

        $rolesInput = $questionHelper->ask(
            $input, $output, new Question(
                sprintf(
                    'Roles (comma separated, choose one or more of the following options %s): ',
                    implode(', ', UserRole::allAsStringArray())
                )
            )
        );
        if (!is_string($rolesInput)) {
            throw new \InvalidArgumentException('Roles must be a string.');
        }

        $localeInput = $questionHelper->ask(
            $input, $output, new Question(
                sprintf(
                    'Locale (choose one of the following options %s): ',
                    implode(', ', Locale::allAsStringArray())
                )
            )
        );
        if (!is_string($localeInput)) {
            throw new \InvalidArgumentException('Locale must be a string.');
        }
        $locale = Locale::from(trim($localeInput));

        $rolesArray = [];
        $roles = explode(',', $rolesInput);
        foreach ($roles as $role) {
            $rolesArray[] = UserRole::from(trim($role));
        }

        $passwordToken = PasswordToken::generate();
        $tokenExpiresAt = $this->clock->now()->modify('+1 day');
        $this->userCredentialRepository->insert(
            new UserCredentials(
                new Roles($rolesArray),
                $emailAddress,
                null,
                $passwordToken,
                $tokenExpiresAt,
                $this->clock->now(),
                $fullName,
                $locale
            )
        );

        $this->messageBus->dispatch(
            new NewUserWasCreated(
                $emailAddress,
                $fullName,
                $passwordToken,
                $tokenExpiresAt,
                $locale
            )
        );

        return Command::SUCCESS;
    }
}
