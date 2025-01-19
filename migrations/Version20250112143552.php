<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20250112143552 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $activityTable = $schema->createTable('activity');
        $activityTable->addColumn('id', Types::GUID);
        $activityTable->addColumn('name', Types::STRING, ['length' => 255]);
        $activityTable->addColumn('description_nl', Types::TEXT);
        $activityTable->addColumn('description_en', Types::TEXT, ['notnull' => false]);
        $activityTable->addColumn('first_activity_date', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $activityTable->addColumn('max_participants', Types::INTEGER, ['notnull' => false]);
        $activityTable->addColumn('subscribe_until', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $activityTable->addColumn('status', Types::STRING, ['length' => 255]);
        $activityTable->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $activityTable->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $activityTable->setPrimaryKey(['id']);

        $activityManagerTable = $schema->createTable('activity_manager');
        $activityManagerTable->addColumn('activity_id', Types::GUID);
        $activityManagerTable->addColumn('email_address', Types::STRING, ['length' => 255]);
        $activityManagerTable->setPrimaryKey(['activity_id', 'email_address']);

        $activityParticipantTable = $schema->createTable('activity_participant');
        $activityParticipantTable->addColumn('participant_id', Types::GUID);
        $activityParticipantTable->addColumn('activity_id', Types::GUID);
        $activityParticipantTable->addColumn('full_name', Types::STRING, ['length' => 255]);
        $activityParticipantTable->addColumn('email_address', Types::STRING, ['length' => 255]);
        $activityParticipantTable->addColumn('phone_number', Types::STRING, ['length' => 255]);
        $activityParticipantTable->addColumn('remarks', Types::TEXT, ['notnull' => false]);
        $activityParticipantTable->addColumn('subscribed_at', Types::DATETIME_IMMUTABLE);
        $activityParticipantTable->setPrimaryKey(['participant_id']);
        $activityParticipantTable->addUniqueConstraint(['full_name', 'email_address', 'activity_id']);

        $completedActivityPartTable = $schema->createTable('completed_activity_part');
        $completedActivityPartTable->addColumn('id', Types::GUID);
        $completedActivityPartTable->addColumn('activity_id', Types::GUID);
        $completedActivityPartTable->addColumn('started_at', Types::DATETIME_IMMUTABLE);
        $completedActivityPartTable->addColumn('activity_duration_minutes', Types::INTEGER);
        $completedActivityPartTable->addColumn('remarks', Types::TEXT, ['notnull' => false]);
        $completedActivityPartTable->addColumn('status', Types::STRING, ['length' => 255]);
        $completedActivityPartTable->setPrimaryKey(['id']);

        $activityParticipantPresenceTable = $schema->createTable('completed_activity_part_participant_presence');
        $activityParticipantPresenceTable->addColumn('completed_activity_part_id', Types::GUID);
        $activityParticipantPresenceTable->addColumn('participant_id', Types::GUID);
        $activityParticipantPresenceTable->setPrimaryKey(['completed_activity_part_id', 'participant_id']);

        $activityManagerPresenceTable = $schema->createTable('completed_activity_part_manager_presence');
        $activityManagerPresenceTable->addColumn('completed_activity_part_id', Types::GUID);
        $activityManagerPresenceTable->addColumn('email_address', Types::STRING, ['length' => 255]);
        $activityManagerPresenceTable->setPrimaryKey(['completed_activity_part_id', 'email_address']);
    }

    public function down(Schema $schema): void
    {
    }
}
