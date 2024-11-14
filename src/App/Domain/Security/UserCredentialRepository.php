<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Security;

use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Utilities\Clock;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserCredentialRepository
{
    public function loadUserByEmailAddress(EmailAddress $emailAddress): UserCredentials;

    public function findByPasswordToken(PasswordToken $passwordToken, Clock $clock): ?UserCredentials;

    public function refreshUser(UserInterface $user): UserCredentials;

    public function insert(UserCredentials $user): void;

    public function update(UserCredentials $user, EmailAddress $oldEmailAddress): void;

    public function remove(EmailAddress $emailAddress): void;
}