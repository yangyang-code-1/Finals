<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011185335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commission ADD artist_id INT NOT NULL, ADD client_id INT NOT NULL');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C650158B7970CF8 FOREIGN KEY (artist_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C65015819EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_1C650158B7970CF8 ON commission (artist_id)');
        $this->addSql('CREATE INDEX IDX_1C65015819EB6921 ON commission (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE transaction');
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C650158B7970CF8');
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C65015819EB6921');
        $this->addSql('DROP INDEX IDX_1C650158B7970CF8 ON commission');
        $this->addSql('DROP INDEX IDX_1C65015819EB6921 ON commission');
        $this->addSql('ALTER TABLE commission DROP artist_id, DROP client_id');
    }
}
