<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\ActivityManager;

interface PossibleActivityManagerRepository
{
    /**
     * @return PossibleActivityManager[]
     */
    public function findAll(): array;
}