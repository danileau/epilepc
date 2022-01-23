<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * In den Repositories sind wiederverwendbare Funktionen definiert, welche ein bestimmtes Doctrine-Query ausführen
 * und die Response retournieren
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function translateMonth($month_name, $lang)
    {

        switch ($lang) {
            case "en":
                switch ($month_name) {
                    case "January":
                        $month = "January";
                        return $month;
                        break;
                    case "February":
                        $month = "February";
                        return $month;
                        break;
                    case "March":
                        $month = "March";
                        return $month;
                        break;
                    case "April":
                        $month = "April";
                        return $month;
                        break;
                    case "May":
                        $month = "May";
                        return $month;
                        break;
                    case "June":
                        $month = "June";
                        return $month;
                        break;
                    case "July":
                        $month = "July";
                        return $month;
                        break;
                    case "August":
                        $month = "August";
                        return $month;
                        break;
                    case "September":
                        $month = "September";
                        return $month;
                        break;
                    case "October":
                        $month = "October";
                        return $month;
                        break;
                    case "November":
                        $month = "November";
                        return $month;
                        break;
                    case "December":
                        $month = "December";
                        return $month;
                        break;
                }
                break;
            case "de":
                switch ($month_name) {
                    case "January":
                        $month = "Januar";
                        return $month;
                        break;
                    case "February":
                        $month = "Februar";
                        return $month;
                        break;
                    case "March":
                        $month = "März";
                        return $month;
                        break;
                    case "April":
                        $month = "April";
                        return $month;
                        break;
                    case "May":
                        $month = "Mai";
                        return $month;
                        break;
                    case "June":
                        $month = "Juni";
                        return $month;
                        break;
                    case "July":
                        $month = "Juli";
                        return $month;
                        break;
                    case "August":
                        $month = "August";
                        return $month;
                        break;
                    case "September":
                        $month = "September";
                        return $month;
                        break;
                    case "October":
                        $month = "Oktober";
                        return $month;
                        break;
                    case "November":
                        $month = "November";
                        return $month;
                        break;
                    case "December":
                        $month = "Dezember";
                        return $month;
                        break;
                }
                break;
            case "it":
                switch ($month_name) {
                    case "January":
                        $month = "Gennaio";
                        return $month;
                        break;
                    case "February":
                        $month = "Febbraio";
                        return $month;
                        break;
                    case "March":
                        $month = "Marzo";
                        return $month;
                        break;
                    case "April":
                        $month = "Aprile";
                        return $month;
                        break;
                    case "May":
                        $month = "Maggio";
                        return $month;
                        break;
                    case "June":
                        $month = "Giugno";
                        return $month;
                        break;
                    case "July":
                        $month = "Luglio";
                        return $month;
                        break;
                    case "August":
                        $month = "Agosto";
                        return $month;
                        break;
                    case "September":
                        $month = "Settembre";
                        return $month;
                        break;
                    case "October":
                        $month = "Ottobre";
                        return $month;
                        break;
                    case "November":
                        $month = "Novembre";
                        return $month;
                        break;
                    case "December":
                        $month = "Dicembre";
                        return $month;
                        break;
                }
                break;
            case "fr":
                switch ($month_name) {
                    case "January":
                        $month = "Janvier";
                        return $month;
                        break;
                    case "February":
                        $month = "Février";
                        return $month;
                        break;
                    case "March":
                        $month = "Mars";
                        return $month;
                        break;
                    case "April":
                        $month = "Avril";
                        return $month;
                        break;
                    case "May":
                        $month = "Mai";
                        return $month;
                        break;
                    case "June":
                        $month = "Juin";
                        return $month;
                        break;
                    case "July":
                        $month = "Juillet";
                        return $month;
                        break;
                    case "August":
                        $month = "Août";
                        return $month;
                        break;
                    case "September":
                        $month = "Septembre";
                        return $month;
                        break;
                    case "October":
                        $month = "Octobre";
                        return $month;
                        break;
                    case "November":
                        $month = "Novembre";
                        return $month;
                        break;
                    case "December":
                        $month = "Décembre";
                        return $month;
                        break;
                }
                break;
        }
    }
}
