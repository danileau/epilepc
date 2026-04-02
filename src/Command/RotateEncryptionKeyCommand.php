<?php

namespace App\Command;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rotates the Defuse encryption key used by doctrine-encrypt-bundle.
 *
 * Reads every encrypted cell (marked with <ENC> suffix), decrypts with
 * the OLD key, re-encrypts with a NEW key, and updates the row —
 * all inside a single DB transaction.
 *
 * Usage:
 *   php bin/console app:rotate-encryption-key --old-key-file=.Defuse.key.old
 *   php bin/console app:rotate-encryption-key --old-key-file=.Defuse.key.old --dry-run
 */
class RotateEncryptionKeyCommand extends Command
{
    protected static $defaultName = 'app:rotate-encryption-key';

    private const ENC_SUFFIX = '<ENC>';

    /**
     * Every table + column pair that carries an @Encrypted annotation.
     */
    private const ENCRYPTED_FIELDS = [
        'user'       => ['firstname', 'lastname', 'diagnose'],
        'seizure'    => ['title', 'description'],
        'diaryentry' => ['title', 'content'],
        'medication' => ['name', 'description', 'dosage'],
        'event'      => ['name', 'description'],
    ];

    private Connection $connection;
    private string $projectDir;

    public function __construct(Connection $connection, string $projectDir)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Rotate the Defuse encryption key and re-encrypt all DB fields')
            ->addOption('old-key-file', null, InputOption::VALUE_REQUIRED,
                'Path to the OLD .Defuse.key file (relative to project root or absolute)')
            ->addOption('new-key-file', null, InputOption::VALUE_OPTIONAL,
                'Path to the NEW .Defuse.key file. If omitted, a fresh key is generated at .Defuse.key')
            ->addOption('dry-run', null, InputOption::VALUE_NONE,
                'Decrypt one sample per table to verify the old key, then stop');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        // ── 1. Load the OLD key ──────────────────────────────────────
        $oldKeyPath = $input->getOption('old-key-file');
        if (!$oldKeyPath) {
            $io->error('--old-key-file is required. Recover it with: git show 927f158:.Defuse.key > .Defuse.key.old');
            return Command::FAILURE;
        }
        if (!str_starts_with($oldKeyPath, '/')) {
            $oldKeyPath = $this->projectDir . '/' . $oldKeyPath;
        }
        if (!file_exists($oldKeyPath)) {
            $io->error("Old key file not found: $oldKeyPath");
            return Command::FAILURE;
        }

        $oldKeyAscii = trim(file_get_contents($oldKeyPath));
        try {
            $oldKey = Key::loadFromAsciiSafeString($oldKeyAscii);
        } catch (\Exception $e) {
            $io->error('Failed to load old key: ' . $e->getMessage());
            return Command::FAILURE;
        }
        $io->success('Old key loaded successfully.');

        // ── 2. Load or generate the NEW key ──────────────────────────
        $newKeyPath = $input->getOption('new-key-file');
        if (!$newKeyPath) {
            $newKeyPath = $this->projectDir . '/.Defuse.key';
        } elseif (!str_starts_with($newKeyPath, '/')) {
            $newKeyPath = $this->projectDir . '/' . $newKeyPath;
        }

        if ($dryRun) {
            $io->note('Dry-run mode — skipping new key generation.');
            $newKey = null;
        } else {
            if (file_exists($newKeyPath)) {
                $io->warning("New key file already exists at $newKeyPath — loading it.");
                $newKeyAscii = trim(file_get_contents($newKeyPath));
                $newKey = Key::loadFromAsciiSafeString($newKeyAscii);
            } else {
                $newKey = Key::createNewRandomKey();
                file_put_contents($newKeyPath, $newKey->saveToAsciiSafeString());
                chmod($newKeyPath, 0600);
                $io->success("New key generated at $newKeyPath (chmod 0600).");
            }
        }

        // ── 3. Iterate tables and re-encrypt ─────────────────────────
        $totalDecrypted = 0;
        $totalFailed = 0;

        $this->connection->beginTransaction();

        try {
            foreach (self::ENCRYPTED_FIELDS as $table => $columns) {
                $io->section("Table: $table");

                $idCol = 'id';
                $rows = $this->connection->fetchAllAssociative(
                    "SELECT $idCol, " . implode(', ', $columns) . " FROM $table"
                );
                $io->text(sprintf('  Found %d rows.', count($rows)));

                $rowsUpdated = 0;

                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $col) {
                        $value = $row[$col];

                        // Skip NULL or unencrypted values
                        if ($value === null || !str_ends_with($value, self::ENC_SUFFIX)) {
                            continue;
                        }

                        $ciphertext = substr($value, 0, -strlen(self::ENC_SUFFIX));

                        try {
                            $plaintext = Crypto::decrypt($ciphertext, $oldKey);
                        } catch (\Exception $e) {
                            $totalFailed++;
                            $io->warning(sprintf(
                                '  DECRYPT FAILED: %s.%s id=%d — %s',
                                $table, $col, $row[$idCol], $e->getMessage()
                            ));
                            continue;
                        }

                        $totalDecrypted++;

                        if ($dryRun) {
                            // Show a redacted preview (first 3 chars)
                            $preview = mb_substr($plaintext, 0, 3) . '***';
                            $io->text(sprintf('  [DRY-RUN] %s.%s id=%d → decrypted OK (%s)', $table, $col, $row[$idCol], $preview));
                            continue;
                        }

                        $newCiphertext = Crypto::encrypt($plaintext, $newKey) . self::ENC_SUFFIX;
                        $updates[$col] = $newCiphertext;
                    }

                    if (!$dryRun && !empty($updates)) {
                        $this->connection->update($table, $updates, [$idCol => $row[$idCol]]);
                        $rowsUpdated++;
                    }
                }

                if (!$dryRun) {
                    $io->text(sprintf('  Updated %d rows.', $rowsUpdated));
                }
            }

            if ($dryRun) {
                $this->connection->rollBack();
                $io->success(sprintf(
                    'Dry-run complete. %d cells decrypted successfully, %d failures.',
                    $totalDecrypted, $totalFailed
                ));
                if ($totalFailed === 0 && $totalDecrypted > 0) {
                    $io->text('The old key is valid. Run again without --dry-run to rotate.');
                }
                return $totalFailed > 0 ? Command::FAILURE : Command::SUCCESS;
            }

            $this->connection->commit();

            $io->success(sprintf(
                'Key rotation complete. %d cells re-encrypted, %d failures.',
                $totalDecrypted, $totalFailed
            ));

            if ($totalFailed > 0) {
                $io->warning('Some cells failed to decrypt — they may use a different key or be corrupted.');
            }

            $io->note([
                'IMPORTANT: The old key file should be securely deleted:',
                "  rm -P $oldKeyPath   # macOS secure delete",
                "  shred -u $oldKeyPath  # Linux secure delete",
                '',
                'Make sure .Defuse.key is in .gitignore (already confirmed).',
                'Back up the new key to a secure secrets manager — NOT git.',
            ]);

        } catch (\Exception $e) {
            $this->connection->rollBack();
            $io->error('Transaction rolled back: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
