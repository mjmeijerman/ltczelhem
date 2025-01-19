<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Activity;

use Assert\Assertion;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<ActivityManager>
 */
final readonly class ActivityManagers implements IteratorAggregate
{
    /**
     * @param array<ActivityManager> $activityManagers
     */
    public function __construct(
        public array $activityManagers,
    ) {
        Assertion::allIsInstanceOf($activityManagers, ActivityManager::class);
    }

    /**
     * @return \ArrayIterator<int, ActivityManager>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->activityManagers);
    }
}