<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Security;

use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserCredentials implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        public readonly Roles $roles,
        public readonly EmailAddress $emailAddress,
        private ?string $encryptedPassword,
        private ?PasswordToken $passwordToken,
        private ?\DateTimeImmutable $tokenExpiresAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $fullName,
        private Locale $locale,
    ) {
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles->toStringArray();
    }

    public function getPassword(): ?string
    {
        return $this->encryptedPassword;
    }

    public function eraseCredentials(): void
    {
    }

    public function setNewPasswordToken(PasswordToken $passwordToken, \DateTimeImmutable $tokenExpiresAt): void
    {
        $this->passwordToken = $passwordToken;
        $this->tokenExpiresAt = $tokenExpiresAt;
    }

    public function setPassword(string $password, UserPasswordHasherInterface $userPasswordEncoder): void
    {
        $this->encryptedPassword = $userPasswordEncoder->hashPassword($this, $password);
        $this->passwordToken = null;
        $this->tokenExpiresAt = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->emailAddress->toString();
    }

    public function encryptedPassword(): ?string
    {
        return $this->encryptedPassword;
    }

    public function passwordToken(): ?PasswordToken
    {
        return $this->passwordToken;
    }

    public function tokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function changeLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }
}
