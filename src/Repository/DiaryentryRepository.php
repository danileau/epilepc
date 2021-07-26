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
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class DiaryentryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Diaryentry::class);
    }


    /**
     * @return Diaryentry[] Returns all Seizures ordered by the newest Timestamp
     */
    public function findAllFromUser($id)
    {

        return $this->findBy(array('user' => $id), array('timestamp_when' => 'DESC'));
    }

    /**
     * @return Diaryentry[] Returns count from all Diaryentry for the Dashboard
     */
    public function countFindAllFromUser($id){
        return $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->andWhere('d.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }


    /**
     * @param $id
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     * Ruft countFindAllFromUser() auf und liefert ein Array mit allen Summen der gefundenen Tagebucheinträge
     * des eingeloggten Users zurück
     */
    public function getDiagramDiaryData($id){
        $month = $this->getDiaryLast2YearsJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getDiaryCountForMonth($id, $value);
        }
        return $data;
    }


    /**
     * @return array von allen Tagebucheinträgen vom letzten Jahr im JSON-Format
     */
    public function getDiaryLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @return array von allen Tagebucheinträgen von den letzten 2 Jahren im JSON-Format
     */
    public function getDiaryLast2YearsJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 24; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }

    /**
     * @param $id
     * @param $month
     * @return doctrine-query mit der Anzahl Tagebucheinträgen für den abgefragten Monat
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDiaryCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d H:i:s ", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));
        $now = new \DateTime($endDate);
        // Jetzt + 1 Tag um einen gerade eben geschriebenen Eintrag, während demselben Tag auf dem Diagramm anzuzeigen;
        // "# <= :now" funktioniert am gleichen Tag nicht wie erwartet
        $now->modify('+1 day');

        $delay = new \DateTime($startDate);

        return $this->createQueryBuilder('dd')
            ->select('count(dd.id)')
            ->where('dd.user = :val')
            ->andWhere('dd.timestamp_when <= :now')
            ->andWhere('dd.timestamp_when >= :delay')
            ->setParameter('val', $id)
            ->setParameter('now', $now)
            ->setParameter('delay', $delay)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
