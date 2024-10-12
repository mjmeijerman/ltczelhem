<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Utilities;

final readonly class SystemClock implements Clock
{
    public function __construct(private \DateTimeZone $timeZone)
    {
    }

    public function now(?\DateTimeZone $timeZone = null): \DateTimeImmutable
    {
        $timeZone = $timeZone ?: $this->timeZone;

        return new \DateTimeImmutable('now', $timeZone);
    }

    public function today(?\DateTimeZone $timeZone = null): Date
    {
        $timeZone = $timeZone ?: $this->timeZone;

        return Date::fromDateTimeImmutable($this->now($timeZone));
    }
}
