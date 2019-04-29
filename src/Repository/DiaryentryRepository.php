<?php

namespace App\Repository;

use App\Entity\Diaryentry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Diaryentry|null find($id, $lockMode = null, $lockVersion = null)
 * @method Diaryentry|null findOneBy(array $criteria, array $orderBy = null)
 * @method Diaryentry[]    findAll()
 * @method Diaryentry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiaryentryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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


    public function getDiagramDiaryData($id){
        $month = $this->getDiaryLastYearJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getDiaryCountForMonth($id, $value);
        }
        return $data;
    }

    public function getDiaryLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }


    public function getDiaryCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));
        $now = new \DateTime($endDate);
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
