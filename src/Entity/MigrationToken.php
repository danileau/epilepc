<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MigrationTokenRepository")
 * @ORM\Table(name="migration_token", indexes={
 *     @ORM\Index(name="migration_token_token_idx", columns={"token"}),
 *     @ORM\Index(name="migration_token_user_idx", columns={"user_id"})
 * })
 *
 * One-shot token a user mints from /app/account to migrate their data to
 * ciphra. The token value is the only credential ciphra's browser-side
 * fetcher will present at GET /api/ciphra-export/{token}.
 *
 * Lifecycle:
 *   - created with expires_at = now + 7 days
 *   - used_at stamped atomically on the first successful export
 *   - re-presentation after used_at is set → 410 token_already_used
 */
class MigrationToken
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expires_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $used_at;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $ip_first_seen;

    /**
     * Stamped when ciphra POSTs to /api/migration-complete/{token} after
     * a successful client-side import. Provides idempotency for the
     * complete endpoint (further POSTs return 200 without redoing the
     * stamp) and an audit trail showing this token's full lifecycle.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $migration_completed_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expires_at;
    }

    public function setExpiresAt(\DateTimeInterface $expires_at): self
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->used_at;
    }

    public function setUsedAt(?\DateTimeInterface $used_at): self
    {
        $this->used_at = $used_at;

        return $this;
    }

    public function getIpFirstSeen(): ?string
    {
        return $this->ip_first_seen;
    }

    public function setIpFirstSeen(?string $ip_first_seen): self
    {
        $this->ip_first_seen = $ip_first_seen;

        return $this;
    }

    public function isExpired(\DateTimeInterface $now): bool
    {
        return $this->expires_at < $now;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function getMigrationCompletedAt(): ?\DateTimeInterface
    {
        return $this->migration_completed_at;
    }

    public function setMigrationCompletedAt(?\DateTimeInterface $migration_completed_at): self
    {
        $this->migration_completed_at = $migration_completed_at;

        return $this;
    }

    public function isMigrationCompleted(): bool
    {
        return $this->migration_completed_at !== null;
    }
}
