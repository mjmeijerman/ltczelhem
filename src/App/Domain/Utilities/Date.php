<?php

namespace LtcZelhem\App\Domain\Utilities;

final class Date
{
    public static function fromDateTimeImmutable(\DateTimeImmutable $datetime): self
    {
        return new self($datetime);
    }

    public static function fromString(string $date): self
    {
        $datetime = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if (false === $datetime) {
            throw new \InvalidArgumentException(sprintf('Invalid date %s', $date));
        }

        return new self($datetime);
    }

    public function modify(string $modifier): void
    {
        $this->datetime = $this->datetime->modify($modifier);
    }

    public function getNextDay(): self
    {
        $datetime = clone $this->datetime;

        return new self($datetime->modify('+1 day'));
    }

    public function getPreviousDay(): self
    {
        $datetime = clone $this->datetime;

        return new self($datetime->modify('-1 day'));
    }

    public function equals(Date $other): bool
    {
        return $other->toString() === $this->toString();
    }

    public function compare(Date $other): int
    {
        if ($this->datetime < $other->datetime) {
            return -1;
        }

        if ($this->datetime > $other->datetime) {
            return 1;
        }

        return 0;
    }

    public function isBefore(Date $otherDate): bool
    {
        return $this->datetime < $otherDate->datetime;
    }

    public function isAfter(Date $otherDate): bool
    {
        $currentDateTime = new \DateTimeImmutable($this->toString());
        $otherDateTime = new \DateTimeImmutable($otherDate->toString());

        return $currentDateTime > $otherDateTime;
    }

    public function between(?Date $start = null, ?Date $end = null, bool $inclusive = true): bool
    {
        if ($inclusive) {
            return $this->betweenInclusive($start, $end);
        }

        return
            (null === $start || $start->datetime < $this->datetime)
            && (null === $end || $end->datetime > $this->datetime);
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->datetime;
    }

    public function toString(): string
    {
        return $this->datetime->format('Y-m-d');
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function format(string $format): string
    {
        return $this->datetime->format($format);
    }

    private function betweenInclusive(?Date $start = null, ?Date $end = null): bool
    {
        return
            (null === $start || $start->datetime <= $this->datetime)
            && (null === $end || $end->datetime >= $this->datetime);
    }

    private function __construct(private \DateTimeImmutable $datetime)
    {
        $datetime = clone $datetime;
        $datetime = $datetime->setTime(0, 0, 0);

        $this->datetime = $datetime;
    }
}
