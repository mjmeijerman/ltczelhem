<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241011175735 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('user_credentials');
        $table->addColumn('email_address', Types::STRING, ['length' => 150]);
        $table->addColumn('full_name', Types::STRING, ['length' => 150]);
        $table->addColumn('locale', Types::STRING, ['length' => 150]);
        $table->addColumn('encrypted_password', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('password_token', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('token_expires_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['email_address']);

        $table = $schema->createTable('user_roles');
        $table->addColumn('email_address', Types::STRING, ['length' => 150]);
        $table->addColumn('role', Types::STRING, ['length' => 150]);
        $table->setPrimaryKey(['email_address', 'role']);

    }

    public function down(Schema $schema): void
    {
    }
}
