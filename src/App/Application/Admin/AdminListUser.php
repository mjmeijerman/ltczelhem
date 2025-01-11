<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\Admin;

use LtcZelhem\App\Domain\Security\Roles;

final readonly class AdminListUser
{
    public function __construct(
        public string $fullName,
        public string $emailAddress,
        public Roles $roles,
    ) {
    }
}