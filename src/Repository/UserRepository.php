<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * One page of users, newest registration first. Only this slice is
     * hydrated/decrypted — the admin index decrypts firstname+lastname per
     * row, and each Defuse decrypt runs a 100k-iteration PBKDF2 (~74ms), so
     * loading every user blows past the 30s execution cap once the user base
     * grows past ~200. Pagination bounds the per-request decrypt count.
     */
    public function findPaginated(int $page, int $perPage): Paginator
    {
        $query = $this->createQueryBuilder('u')
            ->orderBy('u.agreedTermsAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * Aggregate counts for the admin overview cards. Computed over ALL users
     * via SQL COUNT() — never over the paginated slice — so the metrics stay
     * correct regardless of which page is shown. roles/deactivated/migrated_at
     * are plain columns (not encrypted), so these are cheap.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAdmins(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->andWhere('u.roles LIKE :admin')
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDeactivated(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->andWhere('u.deactivated = 1')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countMigrated(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->andWhere('u.migrated_at IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Distinct users who created OR modified any record (seizure / event /
     * medication / diaryentry) since $since. A proxy for "active users":
     * epilepc has no last_login column, so record timestamps are the only
     * retroactive activity signal — used to size the ciphra-migration push.
     */
    public function countActiveSince(\DateTimeInterface $since): int
    {
        // Raw UNION across the four content tables; UNION dedupes user_ids so
        // COUNT(*) is the distinct active-user count. `event` is backticked
        // because EVENT is a reserved word.
        $sql =
            'SELECT COUNT(*) FROM ('
            . ' SELECT user_id FROM seizure WHERE created_at >= :s OR modified_at >= :s'
            . ' UNION SELECT user_id FROM `event` WHERE created_at >= :s OR modified_at >= :s'
            . ' UNION SELECT user_id FROM medication WHERE created_at >= :s OR modified_at >= :s'
            . ' UNION SELECT user_id FROM diaryentry WHERE created_at >= :s OR modified_at >= :s'
            . ') active';

        return (int) $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['s' => $since->format('Y-m-d H:i:s')])
            ->fetchOne();
    }

    public function translateMonth($month_name, $lang)
    {

        switch ($lang) {
            case "en":
                switch ($month_name) {
                    case "January":
                        $month = "January";
                        return $month;
                        break;
                    case "February":
                        $month = "February";
                        return $month;
                        break;
                    case "March":
                        $month = "March";
                        return $month;
                        break;
                    case "April":
                        $month = "April";
                        return $month;
                        break;
                    case "May":
                        $month = "May";
                        return $month;
                        break;
                    case "June":
                        $month = "June";
                        return $month;
                        break;
                    case "July":
                        $month = "July";
                        return $month;
                        break;
                    case "August":
                        $month = "August";
                        return $month;
                        break;
                    case "September":
                        $month = "September";
                        return $month;
                        break;
                    case "October":
                        $month = "October";
                        return $month;
                        break;
                    case "November":
                        $month = "November";
                        return $month;
                        break;
                    case "December":
                        $month = "December";
                        return $month;
                        break;
                }
                break;
            case "de":
                switch ($month_name) {
                    case "January":
                        $month = "Januar";
                        return $month;
                        break;
                    case "February":
                        $month = "Februar";
                        return $month;
                        break;
                    case "March":
                        $month = "März";
                        return $month;
                        break;
                    case "April":
                        $month = "April";
                        return $month;
                        break;
                    case "May":
                        $month = "Mai";
                        return $month;
                        break;
                    case "June":
                        $month = "Juni";
                        return $month;
                        break;
                    case "July":
                        $month = "Juli";
                        return $month;
                        break;
                    case "August":
                        $month = "August";
                        return $month;
                        break;
                    case "September":
                        $month = "September";
                        return $month;
                        break;
                    case "October":
                        $month = "Oktober";
                        return $month;
                        break;
                    case "November":
                        $month = "November";
                        return $month;
                        break;
                    case "December":
                        $month = "Dezember";
                        return $month;
                        break;
                }
                break;
            case "it":
                switch ($month_name) {
                    case "January":
                        $month = "Gennaio";
                        return $month;
                        break;
                    case "February":
                        $month = "Febbraio";
                        return $month;
                        break;
                    case "March":
                        $month = "Marzo";
                        return $month;
                        break;
                    case "April":
                        $month = "Aprile";
                        return $month;
                        break;
                    case "May":
                        $month = "Maggio";
                        return $month;
                        break;
                    case "June":
                        $month = "Giugno";
                        return $month;
                        break;
                    case "July":
                        $month = "Luglio";
                        return $month;
                        break;
                    case "August":
                        $month = "Agosto";
                        return $month;
                        break;
                    case "September":
                        $month = "Settembre";
                        return $month;
                        break;
                    case "October":
                        $month = "Ottobre";
                        return $month;
                        break;
                    case "November":
                        $month = "Novembre";
                        return $month;
                        break;
                    case "December":
                        $month = "Dicembre";
                        return $month;
                        break;
                }
                break;
            case "fr":
                switch ($month_name) {
                    case "January":
                        $month = "Janvier";
                        return $month;
                        break;
                    case "February":
                        $month = "Février";
                        return $month;
                        break;
                    case "March":
                        $month = "Mars";
                        return $month;
                        break;
                    case "April":
                        $month = "Avril";
                        return $month;
                        break;
                    case "May":
                        $month = "Mai";
                        return $month;
                        break;
                    case "June":
                        $month = "Juin";
                        return $month;
                        break;
                    case "July":
                        $month = "Juillet";
                        return $month;
                        break;
                    case "August":
                        $month = "Août";
                        return $month;
                        break;
                    case "September":
                        $month = "Septembre";
                        return $month;
                        break;
                    case "October":
                        $month = "Octobre";
                        return $month;
                        break;
                    case "November":
                        $month = "Novembre";
                        return $month;
                        break;
                    case "December":
                        $month = "Décembre";
                        return $month;
                        break;
                }
                break;
        }
    }
}
