<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190514110730 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE event_seizure');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE event_seizure (event_id INT NOT NULL, seizure_id INT NOT NULL, INDEX IDX_7FC9535271F7E88B (event_id), INDEX IDX_7FC9535222B9923D (seizure_id), PRIMARY KEY(event_id, seizure_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE event_seizure ADD CONSTRAINT FK_7FC9535222B9923D FOREIGN KEY (seizure_id) REFERENCES seizure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_seizure ADD CONSTRAINT FK_7FC9535271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
    }
}
