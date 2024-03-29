<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210801135927 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE medication CHANGE emergency_med emergency_med TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE seizure DROP emergency_med, CHANGE seizuretype_id seizuretype_id INT DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE medication CHANGE emergency_med emergency_med TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE seizure ADD emergency_med TINYINT(1) DEFAULT \'NULL\', CHANGE seizuretype_id seizuretype_id INT DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT \'NULL\'');
    }
}
