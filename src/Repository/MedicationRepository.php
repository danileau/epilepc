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

    public function getDiagramMedicationData($id){
        $month = $this->getMedicationLastYearJSON();
        foreach ($month as $key => $value) {
            $data[$value] = $this->getMedicationCountForMonth($id, $value);
        }
        return $data;
    }

    public function getMedicationLastYearJSON(){
        $months[] = date("Y-m");
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
        }
        return $months;
    }


    public function getMedicationCountForMonth($id, $month){
        //Year: $date[0], Month: $date[1]
        $date = explode('-', $month);

        $startDate = date("Y-m-d", strtotime($date[0]."-".$date[1]."-1"));
        $endDate = date("Y-m-t", strtotime($date[0]."-".$date[1]."-1"));
        $now = new \DateTime($endDate);
        // Jetzt + 1 Tag um einen gerade eben geschriebenen Eintrag, während demselben Tag auf dem Diagramm anzuzeigen;
        // "# <= :now" funktioniert am gleichen Tag nicht wie erwartet
        $now->modify('+1 day');
        $delay = new \DateTime($startDate);

        return $this->createQueryBuilder('md')
            ->select('count(md.id)')
            ->where('md.user = :val')
            ->andWhere('md.timestamp_prescription <= :now')
            ->andWhere('md.timestamp_prescription >= :delay')
            ->setParameter('val', $id)
            ->setParameter('now', $now)
            ->setParameter('delay', $delay)
            ->getQuery()
            ->getSingleScalarResult();
    }


}
