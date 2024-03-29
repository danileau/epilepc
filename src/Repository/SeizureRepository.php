<?php

namespace App\Repository;

use App\Entity\Seizure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


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
        $month = $this->getSeizure2YearsJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getSeizureCountForMonth($id, $value);
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

        foreach ($days as $key => $value) {
            $data[$value] = $this->getDailySeizuresMonth($id, $value);
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
        //$month = "02";
        $month = date("m");
        $year = date("Y");

        $start_date = "01-" . $month . "-" . $year;
        $start_time = strtotime($start_date);

        $end_time = strtotime("+1 month", $start_time);

        for ($i = $start_time; $i < $end_time; $i += 86400) {
            $list[] = date('Y-m-d', $i);
        }

        return $list;
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

}
