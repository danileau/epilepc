<?php

namespace App\Service;

/**
 * Single source of truth for epilepc's deprecation phase.
 *
 * Phase is set explicitly via the EPILEPC_LIFECYCLE_PHASE env var rather
 * than computed from a date. Lets an operator flip back if something
 * misbehaves, and gives QA an easy lever for walkthrough testing.
 *
 * Calendar (documentation only — does not drive the phase):
 *   normal           : default (anything before announce)
 *   announce         : 2026-07-01 → 2026-08-31 (D-92)
 *   warn             : 2026-09-01 → 2026-09-30 (D-30)
 *   readonly         : 2026-10-01 → 2026-10-30 (D-day)
 *   decommission     : 2026-10-31 onward       (D+30)
 *
 * Registered as a Twig global `lifecycle` (see config/packages/twig.yaml)
 * so templates can branch without dependency injection.
 */
class LifecycleService
{
    private const PHASES = ['normal', 'announce', 'warn', 'readonly', 'decommission'];

    /** @var string */
    private $phase;

    /** @var \DateTimeImmutable */
    private $readonlyAt;

    /** @var \DateTimeImmutable */
    private $decommissionAt;

    public function __construct()
    {
        $raw = $_ENV['EPILEPC_LIFECYCLE_PHASE'] ?? $_SERVER['EPILEPC_LIFECYCLE_PHASE'] ?? 'normal';
        $this->phase = in_array($raw, self::PHASES, true) ? $raw : 'normal';

        $readonlyDate = $_ENV['EPILEPC_DECOMMISSION_AT'] ?? $_SERVER['EPILEPC_DECOMMISSION_AT'] ?? '2026-10-01T00:00:00Z';
        $this->readonlyAt = new \DateTimeImmutable($readonlyDate);
        $this->decommissionAt = $this->readonlyAt->modify('+30 days');
    }

    public function currentPhase(): string
    {
        return $this->phase;
    }

    public function isWritesBlocked(): bool
    {
        return in_array($this->phase, ['readonly', 'decommission'], true);
    }

    public function isReadsBlocked(): bool
    {
        return $this->phase === 'decommission';
    }

    /**
     * Registration is disabled from `announce` onward — per the lifecycle plan,
     * once we've announced ciphra, new epilepc accounts no longer make sense.
     */
    public function isRegistrationDisabled(): bool
    {
        return $this->phase !== 'normal';
    }

    public function readonlyDate(): \DateTimeInterface
    {
        return $this->readonlyAt;
    }

    public function decommissionDate(): \DateTimeInterface
    {
        return $this->decommissionAt;
    }

    public function daysUntilReadonly(): int
    {
        return (int) max(0, floor(($this->readonlyAt->getTimestamp() - time()) / 86400));
    }

    public function daysUntilDecommission(): int
    {
        return (int) max(0, floor(($this->decommissionAt->getTimestamp() - time()) / 86400));
    }
}
