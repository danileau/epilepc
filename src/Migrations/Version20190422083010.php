<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190422083010 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE diaryentry DROP FOREIGN KEY FK_6DDEACC09D86650F');
        $this->addSql('DROP INDEX IDX_6DDEACC09D86650F ON diaryentry');
        $this->addSql('ALTER TABLE diaryentry CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE diaryentry ADD CONSTRAINT FK_6DDEACC0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6DDEACC0A76ED395 ON diaryentry (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE diaryentry DROP FOREIGN KEY FK_6DDEACC0A76ED395');
        $this->addSql('DROP INDEX IDX_6DDEACC0A76ED395 ON diaryentry');
        $this->addSql('ALTER TABLE diaryentry CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE diaryentry ADD CONSTRAINT FK_6DDEACC09D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6DDEACC09D86650F ON diaryentry (user_id_id)');
    }
}
