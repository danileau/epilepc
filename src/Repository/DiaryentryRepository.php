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
    
    // /**
    //  * @return Diaryentry[] Returns an array of Diaryentry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Diaryentry
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
