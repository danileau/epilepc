<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210725124215 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE seizure ADD emergency_med TINYINT(1) DEFAULT NULL, CHANGE seizuretype_id seizuretype_id INT DEFAULT NULL, CHANGE modified_at modified_at DATETIME DEFAULT NULL');
        // Backfill — the next migration (20210801135927) does
        // `ALTER TABLE medication CHANGE emergency_med ...` assuming the
        // column already exists. In production it was added manually and
        // never committed as a Doctrine migration. Without this line a
        // fresh install fails. IF NOT EXISTS guard keeps the patch safe
        // for DBs that already got the column the manual way.
        $this->addSql("SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'medication' AND column_name = 'emergency_med')");
        $this->addSql("SET @stmt := IF(@col_exists = 0, 'ALTER TABLE medication ADD emergency_med TINYINT(1) DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE s FROM @stmt');
        $this->addSql('EXECUTE s');
        $this->addSql('DEALLOCATE PREPARE s');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE seizure DROP emergency_med, CHANGE seizuretype_id seizuretype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE medication DROP emergency_med');
    }
}
