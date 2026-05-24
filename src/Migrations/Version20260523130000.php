<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sliding-window IP attempt log for /api/ciphra-export rate limiting
 * (CIPH-729). Hand-rolled because symfony/rate-limiter needs SF 5.x and
 * this project is pinned to 4.4.
 *
 * Cleanup: rows older than 25h are purged by `app:migration:purge-attempts`.
 */
final class Version20260523130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create migration_export_attempt table for hand-rolled rate limiter';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE migration_export_attempt (
            id INT AUTO_INCREMENT NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempted_at DATETIME NOT NULL,
            INDEX migration_attempt_ip_time_idx (ip, attempted_at),
            INDEX migration_attempt_time_idx (attempted_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE migration_export_attempt');
    }
}
