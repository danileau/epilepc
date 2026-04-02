<?php

namespace App\Repository;

use App\Entity\Medication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Medication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Medication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Medication[]    findAll()
 * @method Medication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MedicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medication::class);
    }

    public function findAllFromUser($id, int $limit = 25, int $offset = 0)
    {
        return $this->findBy(
            array('user' => $id),
            array('timestamp_prescription' => 'DESC'),
            $limit,
            $offset
        );
    }

    public function findForOverview($user, int $limit = 15): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, name, timestamp_prescription
                FROM medication
                WHERE user_id = :uid
                ORDER BY timestamp_prescription DESC
                LIMIT :lim";
        return $conn->executeQuery($sql, [
            'uid' => is_object($user) ? $user->getId() : $user,
            'lim' => $limit,
        ], ['lim' => \PDO::PARAM_INT])->fetchAllAssociative();
    }

    public function countFindAllFromUser($id)
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->andWhere('m.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFindAllEmergencyFromUser($id)
    {
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->andWhere('m.user = :val')
            ->andWhere('m.emergency_med = 1')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Monthly active medication counts for the last 24 months.
     * A medication is "active" in a month if its date range overlaps:
     *   date_from <= month_end AND date_to >= month_start
     *
     * Uses a single query to fetch all medication date ranges, then
     * computes overlap in PHP (1 query instead of 25).
     */
    public function getDiagramMedicationData($user)
    {
        $months = $this->generateMonthRange(24);
        $uid = is_object($user) ? $user->getId() : $user;

        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id, date_from, date_to FROM medication WHERE user_id = :uid";
        $ranges = $conn->executeQuery($sql, ['uid' => $uid])->fetchAllAssociative();

        $data = [];
        foreach ($months as $m) {
            $monthStart = new \DateTime($m . '-01');
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
            $count = 0;
            foreach ($ranges as $r) {
                $from = new \DateTime($r['date_from']);
                $to = new \DateTime($r['date_to']);
                if ($from <= $monthEnd && $to >= $monthStart) {
                    $count++;
                }
            }
            $data[$m] = $count;
        }
        return $data;
    }

    /**
     * Daily emergency medication counts for the current month — single GROUP BY.
     */
    public function getDiagramMedicationMonthData($user)
    {
        $days = $this->generateDayRange();
        $monthStart = reset($days) . ' 00:00:00';
        $monthEnd = end($days) . ' 23:59:59';

        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE_FORMAT(date_from, '%Y-%m-%d') AS d, COUNT(*) AS c
                FROM medication
                WHERE user_id = :uid AND date_from >= :start AND date_from <= :end
                  AND emergency_med = 1
                GROUP BY d";
        $rows = $conn->executeQuery($sql, [
            'uid' => is_object($user) ? $user->getId() : $user,
            'start' => $monthStart,
            'end' => $monthEnd,
        ])->fetchAllAssociative();

        $counts = array_column($rows, 'c', 'd');
        $data = [];
        foreach ($days as $d) {
            $data[$d] = (int)($counts[$d] ?? 0);
        }
        return $data;
    }

    private function generateMonthRange(int $months): array
    {
        $list = [date("Y-m")];
        for ($i = 1; $i <= $months; $i++) {
            $list[] = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
        }
        return $list;
    }

    private function generateDayRange(): array
    {
        $start = strtotime(date('Y-m-01'));
        $end = strtotime("+1 month", $start);
        $list = [];
        for ($i = $start; $i < $end; $i += 86400) {
            $list[] = date('Y-m-d', $i);
        }
        return $list;
    }
}
