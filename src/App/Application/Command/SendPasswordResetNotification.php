<?php

declare(strict_types=1);

namespace LtcZelhem\App\Application\Command;

use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Security\PasswordToken;
use LtcZelhem\App\Domain\Utilities\Locale;

final readonly class SendPasswordResetNotification
{
    public function __construct(
        public EmailAddress $emailAddress,
        public string $fullName,
        public PasswordToken $passwordToken,
        public \DateTimeImmutable $tokenExpiresAt,
        public Locale $locale,
    ) {
    }
}
