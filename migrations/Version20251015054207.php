<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015054207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commission ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C650158A76ED395 FOREIGN KEY (user_id) REFERENCES commission (id)');
        $this->addSql('CREATE INDEX IDX_1C650158A76ED395 ON commission (user_id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B7970CF8');
        $this->addSql('DROP INDEX IDX_8D93D649B7970CF8 ON user');
        $this->addSql('ALTER TABLE user DROP artist_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD artist_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B7970CF8 FOREIGN KEY (artist_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8D93D649B7970CF8 ON user (artist_id)');
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C650158A76ED395');
        $this->addSql('DROP INDEX IDX_1C650158A76ED395 ON commission');
        $this->addSql('ALTER TABLE commission DROP user_id');
    }
}
