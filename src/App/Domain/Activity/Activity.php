<?php
declare(strict_types=1);

namespace LtcZelhem\App\Domain\Activity;

use DateTimeImmutable;

final readonly class Activity
{
    public function __construct(
        public ActivityId $id,
        public string $name,
        public string $descriptionDutch,
        public ?string $descriptionEnglish,
        public ?DateTimeImmutable $firstActivityDate,
        public ?int $maxParticipants,
        public ?DateTimeImmutable $subscribeUntil,
        public ActivityManagers $activityManagers,
        public ActivityStatus $activityStatus,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    )
    {
    }
}