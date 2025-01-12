<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\ActivityManager;

final readonly class PossibleActivityManager
{
    public function __construct(
        public string $fullName,
        public string $emailAddress,
    ) {
    }
}