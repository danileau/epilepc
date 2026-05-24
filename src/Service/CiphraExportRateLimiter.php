<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Hand-rolled sliding-window rate limiter for GET /api/ciphra-export/{token}.
 *
 * Caps:
 *   - 10 attempts per 60s per IP
 *   - 100 attempts per 24h per IP
 *
 * The limiter is symfony/rate-limiter–shaped (returns Retry-After in seconds
 * when blocked, null when admitted) but written against a raw Doctrine
 * connection so we don't need the SF 5.x rate-limiter component on a SF 4.4
 * project. See [[project_epilepc_tech_debt]] in agent memory.
 *
 * Successful requests AND rejected requests both record an attempt — that's
 * intentional. A scripted abuser shouldn't be able to enumerate tokens by
 * triggering 404s for free.
 */
class CiphraExportRateLimiter
{
    private const SHORT_WINDOW_SECONDS = 60;
    private const SHORT_WINDOW_LIMIT   = 10;
    private const LONG_WINDOW_SECONDS  = 86400;
    private const LONG_WINDOW_LIMIT    = 100;

    /** @var Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Returns Retry-After seconds if the request is blocked, else null.
     * Records the attempt regardless of admit/deny.
     */
    public function checkAndRecord(string $ip, \DateTimeInterface $now): ?int
    {
        $shortCount = $this->countInWindow($ip, $now, self::SHORT_WINDOW_SECONDS);
        $longCount  = $this->countInWindow($ip, $now, self::LONG_WINDOW_SECONDS);

        // Record the attempt before deciding — abusers shouldn't get a free
        // probe by hitting the rate ceiling.
        $this->record($ip, $now);

        if ($shortCount >= self::SHORT_WINDOW_LIMIT) {
            return self::SHORT_WINDOW_SECONDS;
        }
        if ($longCount >= self::LONG_WINDOW_LIMIT) {
            return self::LONG_WINDOW_SECONDS;
        }
        return null;
    }

    /**
     * Delete rows older than `keepSeconds` from now. Returns affected rows.
     * Called from `app:migration:purge-attempts`.
     */
    public function purgeOlderThan(\DateTimeInterface $now, int $keepSeconds): int
    {
        // SQL-side NOW() to dodge PHP↔DB timezone mismatch (PHP container is
        // Europe/Zurich, MariaDB defaults to UTC — passing PHP-formatted
        // strings makes the window arithmetic incoherent).
        return (int) $this->conn->executeStatement(
            'DELETE FROM migration_export_attempt
             WHERE attempted_at < NOW() - INTERVAL ' . (int) $keepSeconds . ' SECOND'
        );
    }

    private function countInWindow(string $ip, \DateTimeInterface $now, int $seconds): int
    {
        // Both `attempted_at` (written by record() below) and the window
        // boundary use the DB clock — no timezone arithmetic crosses the
        // PHP↔DB boundary.
        $row = $this->conn->executeQuery(
            'SELECT COUNT(*) AS c FROM migration_export_attempt
             WHERE ip = :ip AND attempted_at >= NOW() - INTERVAL ' . (int) $seconds . ' SECOND',
            ['ip' => $ip]
        )->fetch();
        return (int) ($row['c'] ?? 0);
    }

    private function record(string $ip, \DateTimeInterface $now): void
    {
        $this->conn->executeStatement(
            'INSERT INTO migration_export_attempt (ip, attempted_at) VALUES (:ip, NOW())',
            ['ip' => $ip]
        );
    }
}
