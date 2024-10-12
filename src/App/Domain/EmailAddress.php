<?php

declare(strict_types=1);

namespace LtcZelhem\App\Domain;

use Assert\Assertion;

final readonly class EmailAddress
{
    public function __construct(private string $emailAddress)
    {
        Assertion::email($this->emailAddress);
    }

    public function equals(EmailAddress $other): bool
    {
        return $other->emailAddress === $this->emailAddress;
    }

    public function toString(): string
    {
        return $this->emailAddress;
    }

    public function __toString()
    {
        return $this->toString();
    }
}
