<?php

namespace App\Repository;

use App\Entity\Diaryentry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Diaryentry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Diaryentry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Diaryentry[]    findAll()
 * @method Diaryentry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiaryentryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Diaryentry::class);
    }

    public function findAllFromUser($id)
    {
        return $this->findBy(array('user' => $id), array('timestamp_when' => 'DESC'));
    }

    public function countFindAllFromUser($id)
    {
        return $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->andWhere('d.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Monthly counts for the last 24 months — single GROUP BY query.
     */
    public function getDiagramDiaryData($user)
    {
        $months = $this->generateMonthRange(24);
        $start = new \DateTime(end($months) . '-01');

        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE_FORMAT(timestamp_when, '%Y-%m') AS m, COUNT(*) AS c
                FROM diaryentry
                WHERE user_id = :uid AND timestamp_when >= :start
                GROUP BY m";
        $rows = $conn->executeQuery($sql, [
            'uid' => is_object($user) ? $user->getId() : $user,
            'start' => $start->format('Y-m-d'),
        ])->fetchAllAssociative();

        $counts = array_column($rows, 'c', 'm');
        $data = [];
        foreach ($months as $m) {
            $data[$m] = (int)($counts[$m] ?? 0);
        }
        return $data;
    }

    /**
     * Daily counts for the current month — single GROUP BY query.
     */
    public function getDiagramDiaryMonthData($user)
    {
        $days = $this->generateDayRange();
        $monthStart = reset($days) . ' 00:00:00';
        $monthEnd = end($days) . ' 23:59:59';

        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT DATE_FORMAT(timestamp_when, '%Y-%m-%d') AS d, COUNT(*) AS c
                FROM diaryentry
                WHERE user_id = :uid AND timestamp_when >= :start AND timestamp_when <= :end
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
