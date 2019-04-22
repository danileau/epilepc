<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190422105357 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE seizure ADD seizuretype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE seizure ADD CONSTRAINT FK_FA9EFAFDF3018247 FOREIGN KEY (seizuretype_id) REFERENCES seizuretype (id)');
        $this->addSql('CREATE INDEX IDX_FA9EFAFDF3018247 ON seizure (seizuretype_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE seizure DROP FOREIGN KEY FK_FA9EFAFDF3018247');
        $this->addSql('DROP INDEX IDX_FA9EFAFDF3018247 ON seizure');
        $this->addSql('ALTER TABLE seizure DROP seizuretype_id');
    }
}
