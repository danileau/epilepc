<?php

namespace App\Repository;

use App\Entity\Seizuretype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Seizuretype|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seizuretype|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seizuretype[]    findAll()
 * @method Seizuretype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeizuretypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Seizuretype::class);
    }

    // /**
    //  * @return Seizuretype[] Returns an array of Seizuretype objects
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
    public function findOneBySomeField($value): ?Seizuretype
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
