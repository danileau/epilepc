<?php

namespace App\Repository;

use App\Entity\MigrationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MigrationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method MigrationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method MigrationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MigrationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MigrationToken::class);
    }

    /**
     * Returns the user's unused, unexpired token if one exists, else null.
     * Used by the start endpoint to short-circuit when a still-valid link
     * was already minted (we hand the same link back instead of issuing a
     * second one).
     */
    public function findActiveForUser(User $user, \DateTimeInterface $now): ?MigrationToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.used_at IS NULL')
            ->andWhere('t.expires_at > :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('t.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Deletes tokens that are expired AND either used or older than the
     * retention window. Called from a console command on a cron.
     */
    public function deleteStale(\DateTimeInterface $cutoff): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.expires_at < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }
}
