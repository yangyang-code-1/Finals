<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527022929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Firebase Cloud Messaging token fields to users.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        $userTable = $schema->getTable('user');

        if (!$userTable->hasColumn('fcm_token')) {
            $this->addSql('ALTER TABLE `user` ADD fcm_token VARCHAR(512) DEFAULT NULL');
        }

        if (!$userTable->hasColumn('fcm_platform')) {
            $this->addSql('ALTER TABLE `user` ADD fcm_platform VARCHAR(20) DEFAULT NULL');
        }

        if (!$userTable->hasColumn('fcm_token_updated_at')) {
            $this->addSql('ALTER TABLE `user` ADD fcm_token_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('user')) {
            return;
        }

        $userTable = $schema->getTable('user');

        if ($userTable->hasColumn('fcm_token')) {
            $this->addSql('ALTER TABLE `user` DROP fcm_token');
        }

        if ($userTable->hasColumn('fcm_platform')) {
            $this->addSql('ALTER TABLE `user` DROP fcm_platform');
        }

        if ($userTable->hasColumn('fcm_token_updated_at')) {
            $this->addSql('ALTER TABLE `user` DROP fcm_token_updated_at');
        }
    }
}
