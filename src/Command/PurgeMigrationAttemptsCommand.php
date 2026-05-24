<?php

namespace App\Command;

use App\Repository\MigrationTokenRepository;
use App\Service\CiphraExportRateLimiter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cleans up rate-limiter attempt rows + expired migration tokens.
 *
 * Run from cron daily:
 *   0 3 * * *  php /path/to/epilepc/bin/console app:migration:purge-attempts --quiet
 *
 * Options:
 *   --keep-attempt-hours=25   keep recent rate-limit attempts this long (default: 25h)
 *   --keep-token-days=30      keep expired migration tokens this long (default: 30d)
 *   --dry-run                 don't delete, just count
 */
class PurgeMigrationAttemptsCommand extends Command
{
    protected static $defaultName = 'app:migration:purge-attempts';

    /** @var CiphraExportRateLimiter */
    private $rateLimiter;
    /** @var MigrationTokenRepository */
    private $tokens;

    public function __construct(CiphraExportRateLimiter $rateLimiter, MigrationTokenRepository $tokens)
    {
        parent::__construct();
        $this->rateLimiter = $rateLimiter;
        $this->tokens = $tokens;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Purge rate-limiter attempt rows and expired migration tokens')
            ->addOption('keep-attempt-hours', null, InputOption::VALUE_REQUIRED, 'Keep recent rate-limit attempts this many hours', '25')
            ->addOption('keep-token-days', null, InputOption::VALUE_REQUIRED, 'Keep expired migration tokens this many days', '30')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Count only, do not delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        $attemptHours = (int) $input->getOption('keep-attempt-hours');
        $tokenDays    = (int) $input->getOption('keep-token-days');
        $dryRun       = (bool) $input->getOption('dry-run');

        $tokenCutoff = (new \DateTimeImmutable('@' . ($now->getTimestamp() - $tokenDays * 86400)));

        if ($dryRun) {
            $io->note('Dry-run — no rows will be deleted.');
            $io->success(sprintf(
                'Would purge attempts older than %dh and tokens expired before %s',
                $attemptHours,
                $tokenCutoff->format('Y-m-d H:i:s')
            ));
            return 0;
        }

        $attemptsDeleted = $this->rateLimiter->purgeOlderThan($now, $attemptHours * 3600);
        $tokensDeleted   = $this->tokens->deleteStale($tokenCutoff);

        $io->success(sprintf(
            'Purged %d rate-limit attempts (>%dh) and %d expired migration tokens (>%dd).',
            $attemptsDeleted,
            $attemptHours,
            $tokensDeleted,
            $tokenDays
        ));
        return 0;
    }
}
