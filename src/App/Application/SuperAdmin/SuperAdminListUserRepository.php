<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\SuperAdmin;

interface SuperAdminListUserRepository
{
    /**
     * @return SuperAdminListUser[]
     */
    public function findAll(): array;
}