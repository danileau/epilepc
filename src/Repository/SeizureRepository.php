<?php

namespace App\Repository;

use App\Entity\Seizure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;


/**
 * @method Seizure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seizure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seizure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)

 */
class SeizureRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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
    public function countFindAllFromUser($id){
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->andWhere('s.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getDiagramSeizureData($id){
        $month = $this->getSeizureLastYearJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getSeizureCountForMonth($id, $value);
        }
        return $data;
    }

    public function getSeizureLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }


    public function getSeizureCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));
        $now = new \DateTime($endDate);
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


}
