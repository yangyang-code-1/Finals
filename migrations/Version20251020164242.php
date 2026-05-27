<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020164242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // This migration was auto-generated as a squash but conflicts with earlier migrations that
        // already created these tables. All statements are guarded with information_schema checks
        // so this migration is safe to run against a database that is already partially migrated.

        $tables = $this->connection->fetchFirstColumn(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()"
        );
        $tables = array_map('strtolower', $tables);

        if (!in_array('category', $tables, true)) {
            $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(50) DEFAULT NULL, is_active TINYINT(1) NOT NULL, position INT DEFAULT NULL, relation VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('commission', $tables, true)) {
            $this->addSql('CREATE TABLE commission (id INT AUTO_INCREMENT NOT NULL, artist_id INT DEFAULT NULL, client_id INT DEFAULT NULL, category_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL, INDEX IDX_1C650158B7970CF8 (artist_id), INDEX IDX_1C65015819EB6921 (client_id), INDEX IDX_1C65015812469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('product', $tables, true)) {
            $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('transaction', $tables, true)) {
            $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('user', $tables, true)) {
            $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('messenger_messages', $tables, true)) {
            $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Add FK_1C650158B7970CF8 only if it does not already exist
        $fk1Exists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commission'
               AND CONSTRAINT_NAME = 'FK_1C650158B7970CF8'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if ($fk1Exists == 0) {
            $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C650158B7970CF8 FOREIGN KEY (artist_id) REFERENCES user (id)');
        }

        // Add FK_1C65015819EB6921 only if it does not already exist
        $fk2Exists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commission'
               AND CONSTRAINT_NAME = 'FK_1C65015819EB6921'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if ($fk2Exists == 0) {
            $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C65015819EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        }

        // Add FK_1C65015812469DE2 only if it does not already exist
        $fk3Exists = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'commission'
               AND CONSTRAINT_NAME = 'FK_1C65015812469DE2'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if ($fk3Exists == 0) {
            $this->addSql('ALTER TABLE commission ADD CONSTRAINT FK_1C65015812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C650158B7970CF8');
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C65015819EB6921');
        $this->addSql('ALTER TABLE commission DROP FOREIGN KEY FK_1C65015812469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE commission');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
