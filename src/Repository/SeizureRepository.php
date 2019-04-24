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
     * @return Seizure[] Returns count from all Seizure for the Dashboard
     */
    public function countFindAllFromUser($id){
        return $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->andWhere('s.user = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
