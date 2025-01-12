<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Activity;

use IteratorAggregate;

final readonly class ActivityManagers implements IteratorAggregate
{
    /**
     * @param ActivityManager[] $activityManagers
     */
    public function __construct(
        public array $activityManagers,
    ) {
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->activityManagers);
    }

    /**
     * @return ActivityManager[]
     */
    public function toArray(): array
    {
        return $this->activityManagers;
    }
}