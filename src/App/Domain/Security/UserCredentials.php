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
        private Roles $roles,
        private EmailAddress $emailAddress,
        private ?string $encryptedPassword,
        private ?PasswordToken $passwordToken,
        private ?\DateTimeImmutable $tokenExpiresAt,
        public readonly \DateTimeImmutable $createdAt,
        private string $fullName,
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

    public function getRolesAsRoleObject(): Roles
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->encryptedPassword;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->emailAddress;
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

    public function superAdminChangeUserDetails(
        string $fullName,
        EmailAddress $emailAddress,
        Roles $roles,
    ): void {
        $this->fullName = $fullName;
        $this->emailAddress = $emailAddress;
        $this->roles = $roles;
    }
}
