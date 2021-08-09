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
        $month = $this->getMedication2YearsJSON();

        foreach ($month as $key => $value) {
            $data[$value] = $this->getMedicationCountForMonth($id, $value);
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


}
