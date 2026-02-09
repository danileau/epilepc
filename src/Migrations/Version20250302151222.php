<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302151222 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE diaryentry (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title LONGTEXT NOT NULL, content LONGTEXT NOT NULL, timestamp_when DATETIME NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_6DDEACC0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name LONGTEXT NOT NULL, description LONGTEXT NOT NULL, timestamp_when DATETIME NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, INDEX IDX_3BAE0AA7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE medication (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name LONGTEXT NOT NULL, description LONGTEXT NOT NULL, dosage VARCHAR(255) NOT NULL, date_from DATETIME NOT NULL, date_to DATETIME NOT NULL, timestamp_prescription DATETIME NOT NULL, created_at DATETIME NOT NULL, modified_at DATETIME NOT NULL, emergency_med TINYINT(1) DEFAULT NULL, INDEX IDX_5AEE5B70A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seizure (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, seizuretype_id INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, timestamp_when DATETIME NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, title LONGTEXT NOT NULL, INDEX IDX_FA9EFAFDA76ED395 (user_id), INDEX IDX_FA9EFAFDF3018247 (seizuretype_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seizuretype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, firstname LONGTEXT NOT NULL, lastname LONGTEXT NOT NULL, deactivated TINYINT(1) NOT NULL, agreed_terms_at DATETIME NOT NULL, diagnose LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX user_index (email(38), firstname(225), lastname(225), roles(14)), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE diaryentry ADD CONSTRAINT FK_6DDEACC0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE medication ADD CONSTRAINT FK_5AEE5B70A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE seizure ADD CONSTRAINT FK_FA9EFAFDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE seizure ADD CONSTRAINT FK_FA9EFAFDF3018247 FOREIGN KEY (seizuretype_id) REFERENCES seizuretype (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE seizure DROP FOREIGN KEY FK_FA9EFAFDF3018247');
        $this->addSql('ALTER TABLE diaryentry DROP FOREIGN KEY FK_6DDEACC0A76ED395');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7A76ED395');
        $this->addSql('ALTER TABLE medication DROP FOREIGN KEY FK_5AEE5B70A76ED395');
        $this->addSql('ALTER TABLE seizure DROP FOREIGN KEY FK_FA9EFAFDA76ED395');
        $this->addSql('DROP TABLE diaryentry');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE medication');
        $this->addSql('DROP TABLE seizure');
        $this->addSql('DROP TABLE seizuretype');
        $this->addSql('DROP TABLE user');
    }
}
