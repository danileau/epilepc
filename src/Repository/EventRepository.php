<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        $month = $this->getEventLast2YearsJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getEventCountForMonth($id, $value);
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


}
