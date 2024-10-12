<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain\Utilities;

interface Clock
{
    public function now(?\DateTimeZone $timeZone = null): \DateTimeImmutable;

    public function today(?\DateTimeZone $timeZone = null): Date;
}
