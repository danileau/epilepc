<?php

namespace App\Repository;

use App\Entity\Seizure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @method Seizure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seizure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seizure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class SeizureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seizure::class);
    }

    /**
     * @return Seizure[] Returns all Seizures ordered by the newest Timestamp
     */
    public function findAllFromUser($id)
    {

        return $this->findBy(array('user' => $id), array('timestamp_when' => 'DESC'));
    }

    /**
     * @return Seizure[] Returns count from all Seizures for the Dashboard
     */
    public function countFindAllFromUser($id)
    {
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->andWhere('s.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }


    /**
     * @param $id
     * @return mixed liefert ein Array mit allen Summen der gefundenen Anfällen
     * des eingeloggten Users zurück
     */
    public function getDiagramSeizureData($id)
    {
        $months = $this->getSeizure2YearsJSON();

        $startDate = new \DateTime(end($months) . '-01');
        $endDate = new \DateTime(reset($months) . '-01');
        $endDate->modify('+1 month');

        $batchCounts = $this->getMonthlyCountsBatch($id, $startDate, $endDate);

        $data = [];
        foreach ($months as $month) {
            $data[$month] = $batchCounts[$month] ?? 0;
        }
        return $data;
    }

    /**
     * @param $id
     * @return mixed liefert ein Array mit allen Summen der gefundenen Anfällen des aktuellen Monats
     * des eingeloggten Users zurück
     */
    public function getDiagramSeizureMonthData($id)
    {
        $days = $this->getSeizureCurrentMonthJSON();

        $startDate = reset($days) . ' 00:00:00';
        $endDate = end($days) . ' 23:59:59';

        $batchCounts = $this->getDailyCountsBatch($id, $startDate, $endDate);

        $data = [];
        foreach ($days as $day) {
            $data[$day] = $batchCounts[$day] ?? 0;
        }
        return $data;
    }


    /**
     * @return array von allen Anfällen vom letzten Jahr im JSON-Format
     */
    public function getSeizureLastYearJSON()
    {
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Anfällen von den 2 letzten Jahren im JSON-Format
     */
    public function getSeizure2YearsJSON()
    {
        $months[] = date("Y-m");
        for ($i = 1; $i <= 24; $i++) {
            $months[] = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Anfällen vom aktuellen Monat im JSON-Format
     */
    public function getSeizureCurrentMonthJSON()
    {
        $date = new \DateTime(date('Y-m-01'));
        $endDate = clone $date;
        $endDate->modify('+1 month');

        $list = [];
        while ($date < $endDate) {
            $list[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }

        return $list;
    }

    /**
     * Get all monthly counts in a single query using GROUP BY.
     */
    public function getMonthlyCountsBatch($userId, \DateTime $startDate, \DateTime $endDate): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('YEAR(s.timestamp_when) as yr, MONTH(s.timestamp_when) as mo, COUNT(s.id) as cnt')
            ->where('s.user = :userId')
            ->andWhere('s.timestamp_when >= :start')
            ->andWhere('s.timestamp_when < :end')
            ->setParameter('userId', $userId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('yr, mo')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $key = sprintf('%04d-%02d', $row['yr'], $row['mo']);
            $counts[$key] = (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * Get all daily counts for a date range in a single query.
     */
    public function getDailyCountsBatch($userId, string $startDate, string $endDate): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('SUBSTRING(s.timestamp_when, 1, 10) as day, COUNT(s.id) as cnt')
            ->where('s.user = :userId')
            ->andWhere('s.timestamp_when >= :start')
            ->andWhere('s.timestamp_when <= :end')
            ->setParameter('userId', $userId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('day')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['day']] = (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * @param $id
     * @param $month
     * @return mixed mit der Anzahl Anfällen für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSeizureCountForMonth($id, $month)
    {
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0] . "-" . $date[1] . "-1"));
        $endDate = date("Y-m-t", strtotime($date[0] . "-" . $date[1] . "-1"));
        $now = new \DateTime($endDate);
        // Jetzt + 1 Tag um einen gerade eben geschriebenen Eintrag, während demselben Tag auf dem Diagramm anzuzeigen;
        // "# <= :now" funktioniert am gleichen Tag nicht wie erwartet
        $now->modify('+1 day');
        $delay = new \DateTime($startDate);

        return $this->createQueryBuilder('sd')
            ->select('count(sd.id)')
            ->where('sd.user = :val')
            ->andWhere('sd.timestamp_when <= :now')
            ->andWhere('sd.timestamp_when >= :delay')
            ->setParameter('val', $id)
            ->setParameter('now', $now)
            ->setParameter('delay', $delay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
 * @param $id
 * @param $month
 * @return mixed mit der Anzahl Anfällen für den abgefragten Monat
 * @throws \Doctrine\ORM\NonUniqueResultException
 */
    public function getDailySeizuresMonth($id, $month)
    {
        $startDate = $month." 00:00:00";
        $endDate = $month." 23:59:59";

        return $this->createQueryBuilder('sd')
            ->select('count(sd.id)')
            ->where('sd.user = :val')
            ->andWhere('sd.timestamp_when >= :morning')
            ->andWhere('sd.timestamp_when <= :evening')
            ->setParameter('val', $id)
            ->setParameter('morning', $startDate)
            ->setParameter('evening', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Paginate Seizures for a specific user.
     *
     * @return Paginator
     */
    public function paginateByUser(int $userId, int $page, int $limit = 15): Paginator
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('s.timestamp_when', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }
}
