<?php

namespace App\Command;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Verifies that every @Encrypted cell in the database can be decrypted
 * with the current .Defuse.key. Reports per-table/column stats and
 * flags any cells that fail decryption or appear to be stored in plaintext.
 *
 * Usage:
 *   php bin/console app:verify-encryption
 *   php bin/console app:verify-encryption --key-file=/path/to/.Defuse.key
 *   php bin/console app:verify-encryption --show-failures
 */
class VerifyEncryptionCommand extends Command
{
    protected static $defaultName = 'app:verify-encryption';

    private const ENC_SUFFIX = '<ENC>';

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
            ->setDescription('Verify all encrypted DB cells are decryptable with the current key')
            ->addOption('key-file', null, InputOption::VALUE_OPTIONAL,
                'Path to .Defuse.key (default: project root .Defuse.key)')
            ->addOption('show-failures', null, InputOption::VALUE_NONE,
                'Print the id and column of every cell that fails decryption');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $showFailures = $input->getOption('show-failures');

        // ── Load key ─────────────────────────────────────────────────
        $keyPath = $input->getOption('key-file') ?? $this->projectDir . '/.Defuse.key';
        if (!str_starts_with($keyPath, '/')) {
            $keyPath = $this->projectDir . '/' . $keyPath;
        }
        if (!file_exists($keyPath)) {
            $io->error("Key file not found: $keyPath");
            return Command::FAILURE;
        }

        try {
            $key = Key::loadFromAsciiSafeString(trim(file_get_contents($keyPath)));
        } catch (\Exception $e) {
            $io->error('Failed to load key: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->text("Using key: $keyPath");
        $io->newLine();

        // ── Verify every cell ────────────────────────────────────────
        $summaryRows = [];
        $totalOk = 0;
        $totalFail = 0;
        $totalNull = 0;
        $totalPlaintext = 0;
        $failures = [];

        foreach (self::ENCRYPTED_FIELDS as $table => $columns) {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT id, " . implode(', ', $columns) . " FROM $table"
            );

            foreach ($columns as $col) {
                $ok = 0;
                $fail = 0;
                $null = 0;
                $plaintext = 0;

                foreach ($rows as $row) {
                    $value = $row[$col];

                    if ($value === null) {
                        $null++;
                        continue;
                    }

                    if (!str_ends_with($value, self::ENC_SUFFIX)) {
                        $plaintext++;
                        continue;
                    }

                    $ciphertext = substr($value, 0, -strlen(self::ENC_SUFFIX));

                    try {
                        Crypto::decrypt($ciphertext, $key);
                        $ok++;
                    } catch (\Exception $e) {
                        $fail++;
                        $failures[] = [
                            'table'  => $table,
                            'column' => $col,
                            'id'     => $row['id'],
                            'error'  => $e->getMessage(),
                        ];
                    }
                }

                $status = ($fail === 0 && $plaintext === 0) ? 'OK' : '';
                if ($fail > 0) {
                    $status = 'FAIL';
                }
                if ($plaintext > 0) {
                    $status .= ($status ? ' + ' : '') . 'PLAINTEXT';
                }
                if ($ok === 0 && $null === count($rows)) {
                    $status = 'ALL NULL';
                }

                $summaryRows[] = [$table, $col, count($rows), $ok, $null, $plaintext, $fail, $status];

                $totalOk += $ok;
                $totalFail += $fail;
                $totalNull += $null;
                $totalPlaintext += $plaintext;
            }
        }

        // ── Summary table ────────────────────────────────────────────
        $summaryTable = new Table($output);
        $summaryTable->setHeaders(['Table', 'Column', 'Rows', 'Encrypted OK', 'NULL', 'Plaintext', 'Failed', 'Status']);
        $summaryTable->setRows($summaryRows);
        $summaryTable->render();

        $io->newLine();
        $io->text(sprintf(
            'Totals: %d decrypted OK, %d NULL, %d plaintext, %d failed',
            $totalOk, $totalNull, $totalPlaintext, $totalFail
        ));

        // ── Failure details ──────────────────────────────────────────
        if ($totalFail > 0 && $showFailures) {
            $io->newLine();
            $io->section('Decryption failures');
            $failTable = new Table($output);
            $failTable->setHeaders(['Table', 'Column', 'ID', 'Error']);
            foreach ($failures as $f) {
                $failTable->addRow([$f['table'], $f['column'], $f['id'], mb_substr($f['error'], 0, 80)]);
            }
            $failTable->render();
        } elseif ($totalFail > 0) {
            $io->warning(sprintf(
                '%d cells failed decryption. Re-run with --show-failures to see details.',
                $totalFail
            ));
        }

        // ── Plaintext warnings ───────────────────────────────────────
        if ($totalPlaintext > 0) {
            $io->warning(sprintf(
                '%d cells are stored as plaintext (missing <ENC> suffix). '
                . 'These were either written before encryption was enabled or were manually edited. '
                . 'Run "php bin/console doctrine:encrypt:database" to encrypt them.',
                $totalPlaintext
            ));
        }

        // ── Final verdict ────────────────────────────────────────────
        $io->newLine();
        if ($totalFail === 0 && $totalPlaintext === 0) {
            $io->success('All encrypted cells verified — the current key is valid.');
            return Command::SUCCESS;
        }

        if ($totalFail === 0 && $totalPlaintext > 0) {
            $io->note('Key is valid for all encrypted cells, but some plaintext cells need encryption.');
            return Command::SUCCESS;
        }

        $io->error('Some cells could not be decrypted. The key may be wrong or data may be corrupted.');
        return Command::FAILURE;
    }
}
