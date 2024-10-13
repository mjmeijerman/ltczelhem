<?php
declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\DoctrineDbal;

use Doctrine\DBAL\Connection;
use LtcZelhem\App\Application\SuperAdmin\SuperAdminListUser;
use LtcZelhem\App\Application\SuperAdmin\SuperAdminListUserRepository;
use LtcZelhem\App\Domain\Security\Roles;
use LtcZelhem\App\Domain\Security\UserRole;

final readonly class DbalSuperAdminListUserRepository implements SuperAdminListUserRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function findAll(): array
    {
        $userResult = $this->connection->createQueryBuilder()
            ->select('u.full_name', 'u.email_address')
            ->from('user_credentials', 'u')
            ->executeQuery();

        $users = [];
        /** @var array<string, string> $row */
        foreach ($userResult->fetchAllAssociative() as $row) {
            $roleResult = $this->connection->createQueryBuilder()
                ->select('role')
                ->from('user_roles')
                ->where('email_address = :emailAddress')
                ->setParameter('emailAddress', $row['email_address'])
                ->executeQuery();

            $roles = [];
            /** @var array<string, string> $roleRow */
            foreach ($roleResult->fetchAllAssociative() as $roleRow) {
                $roles[] = UserRole::from($roleRow['role']);
            }

            $users[] = new SuperAdminListUser(
                $row['full_name'],
                $row['email_address'],
                new Roles($roles)
            );
        }

        return $users;
    }
}