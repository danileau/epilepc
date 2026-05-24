<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CIPH-728 / epilepc lifecycle slice 1 — migration_token table.
 *
 * Single-use, 7-day-valid tokens minted from /app/account that authorise
 * a one-shot bundle export at GET /api/ciphra-export/{token}.
 */
final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create migration_token table for one-shot ciphra-export authorisation';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE migration_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            ip_first_seen VARCHAR(45) DEFAULT NULL,
            UNIQUE INDEX UNIQ_MIGRATION_TOKEN_TOKEN (token),
            INDEX migration_token_token_idx (token),
            INDEX migration_token_user_idx (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE migration_token ADD CONSTRAINT FK_MIGRATION_TOKEN_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE migration_token');
    }
}
