<?php

namespace LtcZelhem\App\Domain\Utilities;

use Ramsey\Uuid\Uuid as RamseyUuid;

abstract class Uuid
{
    public static function generate(): static
    {
        return new static(RamseyUuid::uuid4()->toString());
    }

    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    final public function __construct(private string $uuid)
    {
        if (!RamseyUuid::isValid($uuid)) {
            throw new \InvalidArgumentException(sprintf('Invalid uuid string "%s" of class "%s"', $uuid, get_class($this)), 400);
        }
        $this->uuid = strtoupper($uuid);
    }
}
