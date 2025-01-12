<?php
declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\DoctrineDbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use LtcZelhem\App\Domain\Activity\Activity;
use LtcZelhem\App\Domain\Activity\ActivityRepository;

final readonly class DbalActivityRepository implements ActivityRepository
{
    public function __construct(
        private Connection $connection,
    )
    {
    }

    public function create(Activity $activity): void
    {
        $this->connection->createQueryBuilder()
            ->insert('activity')
            ->values([
                'id' => ':id',
                'name' => ':name',
                'description_nl' => ':descriptionDutch',
                'description_en' => ':descriptionEnglish',
                'first_activity_date' => ':firstActivityDate',
                'max_participants' => ':maxParticipants',
                'subscribe_until' => ':subscribeUntil',
                'status' => ':activityStatus',
                'created_at' => ':createdAt',
            ])
            ->setParameters(
                [
                    'id' => $activity->id->toString(),
                    'name' => $activity->name,
                    'descriptionDutch' => $activity->descriptionDutch,
                    'descriptionEnglish' => $activity->descriptionEnglish,
                    'firstActivityDate' => $activity->firstActivityDate,
                    'maxParticipants' => $activity->maxParticipants,
                    'subscribeUntil' => $activity->subscribeUntil,
                    'activityStatus' => $activity->activityStatus->value,
                    'createdAt' => $activity->createdAt,
                ],
                [
                    'firstActivityDate' => Types::DATETIME_IMMUTABLE,
                    'subscribeUntil' => Types::DATETIME_IMMUTABLE,
                    'createdAt' => Types::DATETIME_IMMUTABLE,
                ]
            )->executeStatement();

        foreach ($activity->activityManagers->toArray() as $activityManager) {
            $this->connection->createQueryBuilder()
                ->insert('activity_manager')
                ->values([
                    'activity_id' => ':activityId',
                    'email_address' => ':activityManagerEmailAddress',
                ])
                ->setParameters(
                    [
                        'activityId' => $activity->id->toString(),
                        'activityManagerEmailAddress' => $activityManager->emailAddress,
                    ]
                )->executeStatement();
        }
    }

    public function update(Activity $activity): void
    {
        $this->connection->createQueryBuilder()
            ->update('activity')
            ->set('name', ':name')
            ->set('description_nl', ':descriptionDutch')
            ->set('description_en', ':descriptionEnglish')
            ->set('first_activity_date', ':firstActivityDate')
            ->set('max_participants', ':maxParticipants')
            ->set('subscribe_until', ':subscribeUntil')
            ->set('status', ':activityStatus')
            ->set('updated_at', ':updatedAt')
            ->andWhere('id = :id')
            ->setParameters(
                [
                    'id' => $activity->id->toString(),
                    'name' => $activity->name,
                    'descriptionDutch' => $activity->descriptionDutch,
                    'descriptionEnglish' => $activity->descriptionEnglish,
                    'firstActivityDate' => $activity->firstActivityDate,
                    'maxParticipants' => $activity->maxParticipants,
                    'subscribeUntil' => $activity->subscribeUntil,
                    'activityStatus' => $activity->activityStatus->value,
                    'updatedAt' => $activity->updatedAt,
                ],
                [
                    'firstActivityDate' => Types::DATETIME_IMMUTABLE,
                    'subscribeUntil' => Types::DATETIME_IMMUTABLE,
                    'updatedAt' => Types::DATETIME_IMMUTABLE,
                ]
            )->executeStatement();

        $this->connection->createQueryBuilder()
            ->delete('activity_manager')
            ->where('activity_id = :activityId')
            ->setParameter('activityId', $activity->id->toString())
            ->executeStatement();

        foreach ($activity->activityManagers->toArray() as $activityManager) {
            $this->connection->createQueryBuilder()
                ->insert('activity_manager')
                ->values([
                    'activity_id' => ':activityId',
                    'email_address' => ':activityManagerEmailAddress',
                ])
                ->setParameters(
                    [
                        'activityId' => $activity->id->toString(),
                        'activityManagerEmailAddress' => $activityManager->emailAddress,
                    ]
                )->executeStatement();
        }
    }
}