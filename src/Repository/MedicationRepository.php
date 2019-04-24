<?php

namespace App\Repository;

use App\Entity\Medication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Medication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Medication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Medication[]    findAll()
 * @method Medication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MedicationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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
}
