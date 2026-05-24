<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Slice 3b — per-user post-migration lockdown.
 *
 *   users.migrated_at                       NULL until ciphra signals
 *                                           a successful import.
 *   migration_token.migration_completed_at  audit trail + idempotency
 *                                           guard on the complete endpoint.
 *
 * When `users.migrated_at` is non-NULL, the user is locked into
 * read+export-only mode on epilepc regardless of the global lifecycle
 * phase.
 */
final class Version20260523140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Per-user post-migration lockdown: users.migrated_at + migration_token.migration_completed_at';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user ADD migrated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE migration_token ADD migration_completed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user DROP migrated_at');
        $this->addSql('ALTER TABLE migration_token DROP migration_completed_at');
    }
}
