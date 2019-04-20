<?php

namespace App\Repository;

use App\Entity\Seizure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Seizure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seizure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seizure[]    findAll()
 * @method Seizure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeizureRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Seizure::class);
    }

    // /**
    //  * @return Seizure[] Returns an array of Seizure objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Seizure
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
