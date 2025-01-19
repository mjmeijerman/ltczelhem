<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Activity;

final readonly class ActivityManager
{
    public function __construct(
        public string $emailAddress,
    )
    {
    }
}