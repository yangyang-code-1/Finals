<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251015123430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Drop FK_8D93D649B7970CF8 only if it still exists (may have been dropped by Version20251015054207)
        $fkExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user'
               AND CONSTRAINT_NAME = 'FK_8D93D649B7970CF8'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if ($fkExists > 0) {
            $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B7970CF8');
        }

        // Drop index only if it still exists
        $indexExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user'
               AND INDEX_NAME = 'IDX_8D93D649B7970CF8'"
        );
        if ($indexExists > 0) {
            $this->addSql('DROP INDEX IDX_8D93D649B7970CF8 ON user');
        }

        // Drop column only if it still exists
        $colExists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user'
               AND COLUMN_NAME = 'artist_id'"
        );
        if ($colExists > 0) {
            $this->addSql('ALTER TABLE user DROP artist_id');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD artist_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B7970CF8 FOREIGN KEY (artist_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649B7970CF8 ON user (artist_id)');
    }
}
