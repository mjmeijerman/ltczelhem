<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\SuperAdmin;

use LtcZelhem\App\Domain\Security\Roles;

final readonly class SuperAdminListUser
{
    public function __construct(
        public string $fullName,
        public string $emailAddress,
        public Roles $roles,
    ) {
    }
}