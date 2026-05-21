<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015123430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Guard against duplicate execution: Version20251015054207 already dropped
        // FK_8D93D649B7970CF8, the index, and the artist_id column. Skip these
        // statements if the constraint no longer exists to prevent a fatal
        // "Can't DROP ... check that column/key exists" error on deployment.
        $foreignKeys = $this->connection->executeQuery(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user'
               AND CONSTRAINT_NAME = 'FK_8D93D649B7970CF8'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        )->fetchAllAssociative();

        if (!empty($foreignKeys)) {
            $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B7970CF8');
            $this->addSql('DROP INDEX IDX_8D93D649B7970CF8 ON user');
            $this->addSql('ALTER TABLE user DROP artist_id');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD artist_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B7970CF8 FOREIGN KEY (artist_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649B7970CF8 ON user (artist_id)');
    }
}
