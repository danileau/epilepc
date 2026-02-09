<?php

namespace App\Repository;

use App\Entity\Medication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Medication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Medication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Medication[]    findAll()
 * @method Medication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class MedicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medication::class);
    }

    /**
     * @return Medication[] Returns all Medications ordered by the newest Timestamp
     */
    public function findAllFromUser($id)
    {
        return $this->findBy(array('user' => $id), array('timestamp_prescription' => 'DESC'));
    }

    /**
     * @return Medication[] Returns count from all Medications for the Dashboard
     */
    public function countFindAllFromUser($id){
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->andWhere('m.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Medication[] Returns count from all Medications for the Dashboard
     */
    public function countFindAllEmergencyFromUser($id){
        return $this->createQueryBuilder('m')
            ->select('count(m.id)')
            ->andWhere('m.user = :val')
            ->andWhere('m.emergency_med = 1')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @return mixed
     * Liefert ein Array mit allen Summen der gefundenen Medikationen
     * des eingeloggten Users zurück
     */
    public function getDiagramMedicationData($id){
        $months = $this->getMedication2YearsJSON();

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
     * @return mixed liefert ein Array mit allen Summen der gefundenen Medikationen des aktuellen Monats
     * des eingeloggten Users zurück
     */
    public function getDiagramMedicationMonthData($id)
    {
        $days = $this->getMedicationCurrentMonthJSON();

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
     * @return array von allen Medikationen vom letzten Jahr im JSON-Format
     */
    public function getMedicationLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Medikationen vom den letzten 2 Jahren im JSON-Format
     */
    public function getMedication2YearsJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 24; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Tagen des aktuellen Monats
     */
    public function getMedicationCurrentMonthJSON()
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
     * Get all monthly counts in a single query.
     * Medications use date_from/date_to range overlap, so we query once
     * and count overlaps per month in PHP.
     */
    public function getMonthlyCountsBatch($userId, \DateTime $startDate, \DateTime $endDate): array
    {
        $medications = $this->createQueryBuilder('m')
            ->select('m.date_from, m.date_to')
            ->where('m.user = :userId')
            ->andWhere('m.date_from < :end')
            ->andWhere('m.date_to >= :start')
            ->setParameter('userId', $userId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $counts = [];
        $current = clone $startDate;
        while ($current < $endDate) {
            $key = $current->format('Y-m');
            $monthStart = clone $current;
            $monthEnd = clone $current;
            $monthEnd->modify('last day of this month')->modify('+1 day');

            $count = 0;
            foreach ($medications as $med) {
                $from = $med['date_from'];
                $to = $med['date_to'];
                if ($from < $monthEnd && $to >= $monthStart) {
                    $count++;
                }
            }
            $counts[$key] = $count;
            $current->modify('+1 month');
        }
        return $counts;
    }

    /**
     * Get all daily emergency medication counts in a single query.
     */
    public function getDailyCountsBatch($userId, string $startDate, string $endDate): array
    {
        $results = $this->createQueryBuilder('m')
            ->select('SUBSTRING(m.date_from, 1, 10) as day, COUNT(m.id) as cnt')
            ->where('m.user = :userId')
            ->andWhere('m.date_from >= :start')
            ->andWhere('m.date_from <= :end')
            ->andWhere('m.emergency_med = 1')
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
     * @return mixed mit der Anzahl Medikationen für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMedicationCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));

        $now = new \DateTime($endDate);
        // Jetzt + 1 Tag um einen gerade eben geschriebenen Eintrag, während demselben Tag auf dem Diagramm anzuzeigen;
        // "# <= :now" funktioniert am gleichen Tag nicht wie erwartet
        $now->modify('+1 day');

        $delay = new \DateTime($startDate);

        return $this->createQueryBuilder('md')
            ->select('count(md.id)')
            ->where('md.user = :val')
            ->andWhere('md.date_from <= :now')
            ->andWhere('md.date_to >= :delay')
            ->setParameter('val', $id)
            ->setParameter('now', $now)
            ->setParameter('delay', $delay)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @param $month
     * @return mixed mit der Anzahl Medikationen für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDailyMedicationMonth($id, $month)
    {
        $startDate = $month." 00:00:00";
        $endDate = $month." 23:59:59";
        return $this->createQueryBuilder('md')
            ->select('count(md.id)')
            ->where('md.user = :val')
            ->andWhere('md.date_from >= :morning')
            ->andWhere('md.date_from <= :evening')
            ->andWhere('md.emergency_med = 1')
            ->setParameter('val', $id)
            ->setParameter('morning', $startDate)
            ->setParameter('evening', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Paginate Medications for a specific user.
     *
     * @return Paginator
     */
    public function paginateByUser(int $userId, int $page, int $limit = 10): Paginator
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('m.modified_at', 'DESC')
            ->setFirstResult(($page - 1) * $limit)  // offset
            ->setMaxResults($limit);

        return new Paginator($qb);
    }    
}
