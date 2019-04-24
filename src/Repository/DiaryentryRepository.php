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
}
