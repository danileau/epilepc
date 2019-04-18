<?php
/**
 * Created by PhpStorm.
 * User: danileau
 * Date: 16.04.2019
 * Time: 17:03
 */

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class UserAdminController extends AbstractController
{

    // TODO: Admin Bereich implementieren
    /**
     * @Route("/admin/user/new")
     */
    public function new(EntityManagerInterface $em)
    {
        die('todo');

        return new Response(sprintf(
            'User ID: #%d für User %s erstellt!',
            $user->getId(),
            $user->getFirstname()
        ));
    }
}