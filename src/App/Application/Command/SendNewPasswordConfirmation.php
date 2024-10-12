<?php
declare(strict_types=1);

namespace LtcZelhem\App\Application\Command;

use LtcZelhem\App\Domain\EmailAddress;

final readonly class SendNewPasswordConfirmation
{
    public function __construct(
        public EmailAddress $emailAddress,
        public string $fullName,
    ) {
    }
}