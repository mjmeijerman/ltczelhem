<?php
declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\DoctrineDbal;

use Doctrine\DBAL\Connection;
use LtcZelhem\App\Application\ActivityManager\PossibleActivityManager;
use LtcZelhem\App\Application\ActivityManager\PossibleActivityManagerRepository;

final readonly class DbalPossibleActivityManagerRepository implements PossibleActivityManagerRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return PossibleActivityManager[]
     */
    public function findAll(): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('u.full_name', 'u.email_address')
            ->from('user_credentials', 'u')
            ->join('u', 'user_roles', 'r', 'u.email_address = r.email_address')
            ->where('r.role = :role')
            ->setParameter('role', 'ROLE_ACTIVITY_MANAGER');

        $statement = $query->executeQuery();
        $possibleActivityManagers = [];
        /** @var array<string, string> $row */
        foreach ($statement->fetchAllAssociative() as $row) {
            $possibleActivityManagers[] = $this->hydrate($row);
        }

        return $possibleActivityManagers;
    }

    /**
     * @param array<string, string> $row
     */
    private function hydrate(array $row): PossibleActivityManager
    {
        return new PossibleActivityManager(
            $row['full_name'],
            $row['email_address'],
        );
    }
}