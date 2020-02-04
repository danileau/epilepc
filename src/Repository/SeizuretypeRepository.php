<?php

namespace App\Repository;

use App\Entity\Seizuretype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Seizuretype|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seizuretype|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seizuretype[]    findAll()
 * @method Seizuretype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class SeizuretypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seizuretype::class);
    }

}
