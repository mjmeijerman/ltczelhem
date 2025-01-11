<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\Admin;

interface AdminListUserRepository
{
    /**
     * @return AdminListUser[]
     */
    public function findAll(): array;
}