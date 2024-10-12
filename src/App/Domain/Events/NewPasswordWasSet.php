<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Events;

use LtcZelhem\App\Domain\EmailAddress;

final readonly class NewPasswordWasSet
{
    public function __construct(
        public EmailAddress $emailAddress,
        public string $fullName,
    ) {
    }
}