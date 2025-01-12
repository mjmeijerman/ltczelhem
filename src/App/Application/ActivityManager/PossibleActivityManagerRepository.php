<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\ActivityManager;

interface PossibleActivityManagerRepository
{
    public function findAll(): array;
}