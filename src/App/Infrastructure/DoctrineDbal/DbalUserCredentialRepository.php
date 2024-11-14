<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\DoctrineDbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Security\PasswordToken;
use LtcZelhem\App\Domain\Security\Roles;
use LtcZelhem\App\Domain\Security\UserCredentialRepository;
use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Security\UserRole;
use LtcZelhem\App\Domain\Utilities\Clock;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserCredentials>
 */
final readonly class DbalUserCredentialRepository implements UserProviderInterface, UserCredentialRepository, PasswordUpgraderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function loadUserByEmailAddress(EmailAddress $emailAddress): UserCredentials
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('user_credentials')
            ->andWhere('email_address = :emailAddress')
            ->setParameter('emailAddress', $emailAddress->toString());

        $row = $this->connection->fetchAssociative(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );
        if (!$row) {
            throw new UserNotFoundException();
        }

        $roles = $this->getRolesForUser($emailAddress);
        if (0 === $roles->count()) {
            throw new UserNotFoundException();
        }

        /** @var array<string, string> $row */
        return $this->hydrate($row, $roles);
    }

    public function findByPasswordToken(PasswordToken $passwordToken, Clock $clock): ?UserCredentials
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('user_credentials')
            ->andWhere('password_token = :passwordToken')
            ->andWhere('token_expires_at > :now')
            ->setParameters(
                [
                    'passwordToken' => $passwordToken->toString(),
                    'now' => $clock->now(),
                ],
                ['now' => Types::DATETIME_IMMUTABLE]
            );

        $row = $this->connection->fetchAssociative(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );
        if (!$row) {
            return null;
        }

        /** @var array<string, string> $row */
        $roles = $this->getRolesForUser(new EmailAddress($row['email_address']));
        if (0 === $roles->count()) {
            throw new UserNotFoundException();
        }

        return $this->hydrate($row, $roles);
    }

    public function refreshUser(UserInterface $user): UserCredentials
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByEmailAddress(new EmailAddress($user->getUserIdentifier()));
    }

    public function insert(UserCredentials $user): void
    {
        $this->connection->createQueryBuilder()
            ->insert('user_credentials')
            ->values(
                [
                    'email_address' => ':emailAddress',
                    'password_token' => ':passwordToken',
                    'token_expires_at' => ':tokenExpiresAt',
                    'created_at' => ':createdAt',
                    'full_name' => ':fullName',
                    'locale' => ':locale',
                ]
            )->setParameters(
                [
                    'emailAddress' => $user->getEmailAddress()->toString(),
                    'passwordToken' => $user->passwordToken()?->toString(),
                    'tokenExpiresAt' => $user->tokenExpiresAt(),
                    'createdAt' => $user->createdAt,
                    'fullName' => $user->getFullName(),
                    'locale' => $user->locale()->value,
                ],
                [
                    'tokenExpiresAt' => Types::DATETIME_IMMUTABLE,
                    'createdAt' => Types::DATETIME_IMMUTABLE,
                ]
            )->executeQuery();

        $this->insertUserRoles($user->getEmailAddress(), $user->getRoles());
    }

    public function update(UserCredentials $user, EmailAddress $oldEmailAddress): void
    {
        $this->connection->createQueryBuilder()
            ->update('user_credentials')
            ->set('encrypted_password', ':password')
            ->set('password_token', ':passwordToken')
            ->set('token_expires_at', ':tokenExpiresAt')
            ->set('locale', ':locale')
            ->set('email_address', ':emailAddress')
            ->set('full_name', ':fullName')
            ->andWhere('email_address = :oldEmailAddress')
            ->setParameters(
                [
                    'password' => $user->encryptedPassword(),
                    'passwordToken' => $user->passwordToken()?->toString(),
                    'tokenExpiresAt' => $user->tokenExpiresAt()?->format('Y-m-d H:i:s'),
                    'locale' => $user->locale()->value,
                    'emailAddress' => $user->getEmailAddress()->toString(),
                    'oldEmailAddress' => $oldEmailAddress->toString(),
                    'fullName' => $user->getFullName(),
                ]
            )->executeQuery();

        $this->replaceUserRoles($user->getEmailAddress(), $user->getRoles());
    }

    public function remove(EmailAddress $emailAddress): void
    {
        $this->connection->createQueryBuilder()
            ->delete('user_credentials')
            ->andWhere('email_address = :emailAddress')
            ->setParameter('emailAddress', $emailAddress->toString())
            ->executeQuery();

        $this->connection->createQueryBuilder()
            ->delete('user_roles')
            ->andWhere('email_address = :emailAddress')
            ->setParameter('emailAddress', $emailAddress->toString())
            ->executeQuery();
    }

    public function supportsClass(string $class): bool
    {
        return UserCredentials::class === $class;
    }

    /**
     * @param array<string, string> $row
     */
    private function hydrate(array $row, Roles $roles): UserCredentials
    {
        return new UserCredentials(
            $roles,
            new EmailAddress($row['email_address']),
            $row['encrypted_password'],
            $row['password_token'] ? new PasswordToken($row['password_token']) : null,
            $row['token_expires_at'] ? new \DateTimeImmutable($row['token_expires_at']) : null,
            new \DateTimeImmutable($row['created_at']),
            $row['full_name'],
            Locale::from($row['locale']),
        );
    }

    private function getRolesForUser(EmailAddress $emailAddress): Roles
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('user_roles')
            ->andWhere('email_address = :emailAddress')
            ->setParameter('emailAddress', $emailAddress->toString());

        $rows = $this->connection->fetchAllAssociative(
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters(),
            $queryBuilder->getParameterTypes()
        );

        $roles = [];
        /** @var array<string, string> $row */
        foreach ($rows as $row) {
            $roles[] = UserRole::from($row['role']);
        }

        return new Roles($roles);
    }

    /**
     * @param array<string> $roles
     */
    private function replaceUserRoles(EmailAddress $emailAddress, array $roles): void
    {
        $this->connection->createQueryBuilder()
            ->delete('user_roles')
            ->andWhere('email_address = :emailAddress')
            ->setParameter('emailAddress', $emailAddress->toString())
            ->executeQuery();

        $this->insertUserRoles($emailAddress, $roles);
    }

    /**
     * @param array<string> $roles
     */
    private function insertUserRoles(EmailAddress $emailAddress, array $roles): void
    {
        foreach ($roles as $role) {
            $this->connection->createQueryBuilder()
                ->insert('user_roles')
                ->values(
                    [
                        'email_address' => ':emailAddress',
                        'role' => ':role',
                    ]
                )->setParameters(
                    [
                        'emailAddress' => $emailAddress->toString(),
                        'role' => $role,
                    ],
                )->executeQuery();
        }
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByEmailAddress(new EmailAddress($identifier));
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }
}
