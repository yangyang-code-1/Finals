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
        if (!$schema->hasTable('user')) {
            return;
        }
    
        $table = $schema->getTable('user');
    
        if ($table->hasForeignKey('FK_8D93D649B7970CF8')) {
            $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B7970CF8');
        }
    
        if ($table->hasIndex('IDX_8D93D649B7970CF8')) {
            $this->addSql('DROP INDEX IDX_8D93D649B7970CF8 ON user');
        }
    
        if ($table->hasColumn('artist_id')) {
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
