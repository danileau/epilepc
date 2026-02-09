<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }


    /**
     * @return Event[] Returns all Events ordered by the newest Timestamp
     */
    public function findAllFromUser($id)
    {
        return $this->findBy(array('user' => $id), array('timestamp_when' => 'DESC'));
    }

    /**
     * @return Event[] Returns count from all Events for the Dashboard
     */
    public function countFindAllFromUser($id){
        return $this->createQueryBuilder('e')
            ->select('count(e.id)')
            ->andWhere('e.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @return mixed
     * Ruft countFindAllFromUser() auf und liefert ein Array mit allen Summen der gefundenen Ereignisse
     * des eingeloggten Users zurück
     */
    public function getDiagramEventData($id){
        $months = $this->getEventLast2YearsJSON();

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
     * @return mixed liefert ein Array mit allen Summen der gefundenen Ereignisse des aktuellen Monats
     * des eingeloggten Users zurück
     */
    public function getDiagramEventMonthData($id)
    {
        $days = $this->getEventCurrentMonthJSON();

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
 * @return array von allen Eregnissen vom letzten Jahr im JSON-Forma
 */
    public function getEventLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Eregnissen von den letzten 2 Jahren im JSON-Forma
     */
    public function getEventLast2YearsJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 24; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Tagen des aktuellen Monats
     */
    public function getEventCurrentMonthJSON()
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
        $results = $this->createQueryBuilder('e')
            ->select('YEAR(e.timestamp_when) as yr, MONTH(e.timestamp_when) as mo, COUNT(e.id) as cnt')
            ->where('e.user = :userId')
            ->andWhere('e.timestamp_when >= :start')
            ->andWhere('e.timestamp_when < :end')
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
        $results = $this->createQueryBuilder('e')
            ->select('SUBSTRING(e.timestamp_when, 1, 10) as day, COUNT(e.id) as cnt')
            ->where('e.user = :userId')
            ->andWhere('e.timestamp_when >= :start')
            ->andWhere('e.timestamp_when <= :end')
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
     * @return mixed mit der Anzahl Ereignissen für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getEventCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));
        $now = new \DateTime($endDate);
        // Jetzt + 1 Tag um einen gerade eben geschriebenen Eintrag, während demselben Tag auf dem Diagramm anzuzeigen;
        // "# <= :now" funktioniert am gleichen Tag nicht wie erwartet
        $now->modify('+1 day');
        $delay = new \DateTime($startDate);

        return $this->createQueryBuilder('ed')
            ->select('count(ed.id)')
            ->where('ed.user = :val')
            ->andWhere('ed.timestamp_when <= :now')
            ->andWhere('ed.timestamp_when >= :delay')
            ->setParameter('val', $id)
            ->setParameter('now', $now)
            ->setParameter('delay', $delay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @param $month
     * @return mixed mit der Anzahl Ereignisse für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDailyEventMonth($id, $month)
    {
        $startDate = $month." 00:00:00";
        $endDate = $month." 23:59:59";

        return $this->createQueryBuilder('ed')
            ->select('count(ed.id)')
            ->where('ed.user = :val')
            ->andWhere('ed.timestamp_when >= :morning')
            ->andWhere('ed.timestamp_when <= :evening')
            ->setParameter('val', $id)
            ->setParameter('morning', $startDate)
            ->setParameter('evening', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Paginate Events for a specific user.
     *
     * @return Paginator
     */
    public function paginateByUser(int $userId, int $page, int $limit = 15): Paginator
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.timestamp_when', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }
}
